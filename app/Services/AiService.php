<?php

namespace App\Services;

use App\Models\Allergy;
use App\Models\Condition;
use App\Models\FamilyHistory;
use App\Models\HealthRecord;
use App\Models\Ingredient;
use App\Models\Medication;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;

final class AiService
{
    public function isConfigured(): bool
    {
        return Setting::get('ai_enabled', '0') === '1'
            && filled($this->apiKey());
    }

    public function provider(): string
    {
        return Setting::get('ai_provider', 'anthropic');
    }

    public function model(): string
    {
        return Setting::get('ai_model', $this->defaultModel());
    }

    public function defaultModel(): string
    {
        return match ($this->provider()) {
            'google' => 'gemini-2.5-flash',
            'custom' => '',
            default => 'claude-3-7-sonnet-20250219',
        };
    }

    public function apiKey(): ?string
    {
        $stored = Setting::get('ai_api_key');
        if (! $stored) {
            return null;
        }
        try {
            return decrypt($stored);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Send a chat message and return the assistant's response text.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function chat(User $user, array $history): string
    {
        $systemPrompt = $this->buildSystemPrompt($user);

        return match ($this->provider()) {
            'google' => $this->callGoogle($systemPrompt, $history),
            'custom' => $this->callOpenAiCompatible($systemPrompt, $history),
            default => $this->callAnthropic($systemPrompt, $history),
        };
    }

    /**
     * Generate a recipe — overused ingredients are physically removed from the pantry
     * in the system prompt so the AI cannot see or select them.
     *
     * @param  string[]  $excludeIngredientNames  Simplified names (parentheticals already stripped)
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function chatForRecipe(User $user, array $history, array $excludeIngredientNames = []): string
    {
        $systemPrompt = $this->buildSystemPrompt($user, $excludeIngredientNames);

        return match ($this->provider()) {
            'google' => $this->callGoogle($systemPrompt, $history),
            'custom' => $this->callOpenAiCompatible($systemPrompt, $history),
            default => $this->callAnthropic($systemPrompt, $history),
        };
    }

    public function testConnection(): string
    {
        $probe = [['role' => 'user', 'content' => 'Respond with exactly: "Connection successful."']];

        return match ($this->provider()) {
            'google' => $this->callGoogle('You are a test assistant.', $probe),
            'custom' => $this->callOpenAiCompatible('You are a test assistant.', $probe),
            default => $this->callAnthropic('You are a test assistant.', $probe),
        };
    }

    /**
     * Fetch available model IDs from the configured provider.
     *
     * @return array<string, string> ['model-id' => 'Display Name']
     */
    public function fetchAvailableModels(): array
    {
        return match ($this->provider()) {
            'google' => $this->fetchGoogleModels(),
            'custom' => [],
            default => $this->fetchAnthropicModels(),
        };
    }

    private function fetchGoogleModels(): array
    {
        $response = Http::timeout(15)
            ->get('https://generativelanguage.googleapis.com/v1beta/models', [
                'key' => $this->apiKey(),
                'pageSize' => 100,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($this->friendlyError('Gemini', $response->status(), $response->body()));
        }

        return collect($response->json('models', []))
            ->filter(fn ($m) => in_array('generateContent', $m['supportedGenerationMethods'] ?? []))
            ->sortBy('displayName')
            ->mapWithKeys(fn ($m) => [
                str_replace('models/', '', $m['name']) => $m['displayName'],
            ])
            ->all();
    }

    private function fetchAnthropicModels(): array
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'x-api-key' => $this->apiKey(),
                'anthropic-version' => '2023-06-01',
            ])
            ->get('https://api.anthropic.com/v1/models', ['limit' => 100]);

        if ($response->failed()) {
            throw new \RuntimeException($this->friendlyError('Anthropic', $response->status(), $response->body()));
        }

        return collect($response->json('data', []))
            ->sortByDesc('created_at')
            ->mapWithKeys(fn ($m) => [$m['id'] => $m['display_name'] ?? $m['id']])
            ->all();
    }

    // ── Providers ───────────────────────────────────────────────────────────

    private function callAnthropic(string $system, array $history): string
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'x-api-key' => $this->apiKey(),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model(),
                'max_tokens' => 4096,
                'system' => $system,
                'messages' => $history,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($this->friendlyError('Anthropic', $response->status(), $response->body()));
        }

        return $response->json('content.0.text', '');
    }

    private function callGoogle(string $system, array $history): string
    {
        $contents = collect($history)->map(fn ($m) => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ])->values()->all();

        $model = $this->model();
        $response = Http::timeout(60)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey()}", [
                'system_instruction' => ['parts' => [['text' => $system]]],
                'contents' => $contents,
                'generationConfig' => ['maxOutputTokens' => 8192],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($this->friendlyError('Gemini', $response->status(), $response->body()));
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    private function callOpenAiCompatible(string $system, array $history): string
    {
        $baseUrl = rtrim(Setting::get('ai_custom_base_url', ''), '/');

        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $history,
        );

        $response = Http::timeout(60)
            ->withHeaders(['Authorization' => 'Bearer '.$this->apiKey()])
            ->post("{$baseUrl}/chat/completions", [
                'model' => $this->model(),
                'messages' => $messages,
                'max_tokens' => 4096,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($this->friendlyError('Custom endpoint', $response->status(), $response->body()));
        }

        return $response->json('choices.0.message.content', '');
    }

    private function friendlyError(string $provider, int $status, string $body): string
    {
        return match ($status) {
            401, 403 => "{$provider}: Invalid or missing API key. Check your key in Settings → AI Assistant.",
            429 => "{$provider}: Rate limit reached. Wait a moment before trying again.",
            503, 529 => "{$provider}: Service temporarily unavailable due to high demand. Please try again in a few seconds.",
            404 => "{$provider}: Model not found. Check the model name in Settings → AI Assistant.",
            default => "{$provider}: Unexpected error (HTTP {$status}). Try again or check Settings.",
        };
    }

    // ── Profile prompt ───────────────────────────────────────────────────────

    private function buildSystemPrompt(User $user, array $excludeIngredientNames = []): string
    {
        $profile = $user->profile;
        $info = $user->personalInfo;
        $lifestyle = $user->lifestyleProfile;
        $age = $profile?->dob ? now()->diffInYears($profile->dob) : null;
        $latestRecord = HealthRecord::where('user_id', $user->id)
            ->orderBy('date', 'desc')->first();

        $conditions = Condition::where('user_id', $user->id)
            ->whereIn('status', ['Active', 'Managed'])->get();
        $medications = Medication::where('user_id', $user->id)
            ->where('status', 'Active')->get();
        $allergies = Allergy::where('user_id', $user->id)->get();
        $family = FamilyHistory::where('user_id', $user->id)->get();

        $recentRecords = HealthRecord::where('user_id', $user->id)
            ->orderBy('date', 'desc')->take(7)->get();

        $excludeLower = array_map('strtolower', $excludeIngredientNames);
        $ingredients = Ingredient::where('user_id', $user->id)
            ->orderBy('category')->orderBy('name')->get()
            ->filter(function ($i) use ($excludeLower) {
                $simplified = strtolower(trim(preg_replace('/\s*\(.*?\)/', '', $i->name)));

                return ! in_array($simplified, $excludeLower);
            });

        $lines = [
            'You are a personal health assistant. Use the profile below to give this specific user personalised, evidence-based guidance on health, nutrition, medications, and exercise.',
            'Always recommend consulting a licensed healthcare professional before making medical decisions. You provide information, not diagnosis or prescriptions.',
            '',
            '=== USER HEALTH PROFILE ===',
            '',
            '--- Biometrics ---',
            'Name: '.$user->name,
        ];

        if ($age) {
            $lines[] = 'Age: '.$age.' years';
        }
        if ($profile?->gender) {
            $lines[] = 'Gender: '.$profile->gender->value;
        }
        if ($profile?->height_cm) {
            $lines[] = 'Height: '.$profile->height_cm.' cm';
        }
        if ($info?->blood_type) {
            $lines[] = 'Blood type: '.$info->blood_type;
        }
        if ($info?->pronouns) {
            $lines[] = 'Pronouns: '.$info->pronouns;
        }

        if ($latestRecord) {
            $lines[] = '';
            $lines[] = '--- Latest Measurements ('.($latestRecord->date?->format('Y-m-d') ?? 'unknown').') ---';
            if ($latestRecord->weight) {
                $lines[] = 'Weight: '.$latestRecord->weight.' kg';
            }
            if ($latestRecord->systolic) {
                $lines[] = 'Blood pressure: '.$latestRecord->systolic.'/'.$latestRecord->diastolic.' mmHg';
            }
            if ($latestRecord->pulse) {
                $lines[] = 'Pulse: '.$latestRecord->pulse.' bpm';
            }
            if ($latestRecord->water_intake_l) {
                $lines[] = 'Water intake: '.$latestRecord->water_intake_l.' L';
            }
            if ($latestRecord->exercise_minutes) {
                $lines[] = 'Exercise: '.$latestRecord->exercise_minutes.' min';
            }
        }

        if ($profile) {
            $lines[] = '';
            $lines[] = '--- Baselines ---';
            $lines[] = 'Starting weight: '.$profile->baseline_weight.' kg';
            $lines[] = 'Resting pulse: '.$profile->baseline_pulse.' bpm';
            $lines[] = 'Baseline BP: '.$profile->baseline_systolic.'/'.$profile->baseline_diastolic.' mmHg';
        }

        $lines[] = '';
        $lines[] = '--- Goals ---';
        if ($profile?->target_weight) {
            $lines[] = 'Target weight: '.$profile->target_weight.' kg';
        }
        if ($profile?->daily_calorie_goal) {
            $lines[] = 'Daily calories: '.$profile->daily_calorie_goal.' kcal';
        }
        if ($profile?->daily_water_goal) {
            $lines[] = 'Daily water: '.$profile->daily_water_goal.' L';
        }
        if ($profile?->weekly_exercise_goal) {
            $lines[] = 'Weekly exercise: '.$profile->weekly_exercise_goal.' min';
        }

        if ($conditions->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '--- Active Health Conditions ---';
            foreach ($conditions as $c) {
                $line = '- '.$c->name.' (Status: '.$c->status.')';
                if ($c->diagnosis_year) {
                    $line .= ', diagnosed '.$c->diagnosis_year;
                }
                if ($c->specialist) {
                    $line .= ', managed by '.$c->specialist;
                }
                $lines[] = $line;
            }
        }

        if ($medications->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '--- Current Medications & Supplements ---';
            foreach ($medications as $m) {
                $line = '- '.$m->name.' ('.$m->dosage.', '.$m->frequency.')';
                if ($m->timing) {
                    $line .= ' — '.$m->timing;
                }
                if ($m->reason) {
                    $line .= '; reason: '.$m->reason;
                }
                if ($m->prescribing_doctor) {
                    $line .= '; prescribed by '.$m->prescribing_doctor;
                }
                $lines[] = $line;
            }
        }

        if ($allergies->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '--- Allergies & Sensitivities ---';
            foreach ($allergies as $a) {
                $line = '- '.$a->allergen.' ('.$a->category.', severity: '.$a->severity.')';
                if ($a->reaction) {
                    $line .= '; reaction: '.$a->reaction;
                }
                if ($a->treatment) {
                    $line .= '; protocol: '.$a->treatment;
                }
                $lines[] = $line;
            }
        }

        if ($family->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '--- Family Medical History ---';
            foreach ($family as $f) {
                $line = '- '.$f->relative.': '.$f->conditions;
                if ($f->onset) {
                    $line .= ' (onset: '.$f->onset.')';
                }
                if ($f->status) {
                    $line .= ' — '.$f->status;
                }
                $lines[] = $line;
            }
        }

        if ($lifestyle) {
            $lines[] = '';
            $lines[] = '--- Lifestyle ---';
            if ($lifestyle->dietary_regimen) {
                $lines[] = 'Diet: '.$lifestyle->dietary_regimen;
                $mealContext = $this->interpretMealPattern($lifestyle->dietary_regimen, $profile?->daily_calorie_goal);
                if ($mealContext) {
                    $lines[] = $mealContext;
                }
            }
            if ($lifestyle->food_restrictions) {
                $lines[] = 'Food restrictions: '.$lifestyle->food_restrictions;
            }
            if ($lifestyle->caffeine_intake) {
                $lines[] = 'Caffeine: '.$lifestyle->caffeine_intake;
            }
            if ($lifestyle->physical_activity) {
                $lines[] = 'Activity: '.$lifestyle->physical_activity;
            }
            if ($lifestyle->sleep_hours) {
                $lines[] = 'Sleep: '.$lifestyle->sleep_hours.' hrs/night';
            }
            if ($lifestyle->sleep_notes) {
                $lines[] = 'Sleep notes: '.$lifestyle->sleep_notes;
            }
            if ($lifestyle->tobacco_use) {
                $lines[] = 'Tobacco: '.$lifestyle->tobacco_use;
            }
            if ($lifestyle->alcohol_use) {
                $lines[] = 'Alcohol: '.$lifestyle->alcohol_use;
            }
        }

        if ($recentRecords->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '--- Recent Health Records (last 7 entries) ---';
            foreach ($recentRecords as $r) {
                $parts = [$r->date?->format('Y-m-d') ?? 'unknown'];
                if ($r->weight) {
                    $parts[] = 'weight '.$r->weight.'kg';
                }
                if ($r->systolic) {
                    $parts[] = 'BP '.$r->systolic.'/'.$r->diastolic;
                }
                if ($r->pulse) {
                    $parts[] = 'pulse '.$r->pulse;
                }
                if ($r->exercise_minutes) {
                    $parts[] = 'exercise '.$r->exercise_minutes.'min';
                }
                $lines[] = '- '.implode(', ', $parts);
            }
        }

        if ($ingredients->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '--- Pantry / Available Ingredients ---';
            $lines[] = 'RECIPE CONSTRAINT: When generating recipes, you may ONLY use ingredients from this list. Do not suggest, substitute, or assume the availability of any ingredient not listed here.';
            foreach ($ingredients as $i) {
                $line = '- '.$i->name;
                if ($i->quantity) {
                    $line .= ' ('.$i->quantity.')';
                }
                if ($i->quantity_on_hand !== null) {
                    $stock = $i->quantity_on_hand <= 0 ? 'depleted' : $i->quantity_on_hand.' units remaining';
                    $line .= " [$stock]";
                }
                if ($i->category) {
                    $line .= ' {'.$i->category.'}';
                }
                $lines[] = $line;
            }
        }

        $lines[] = '';
        $lines[] = '=== END OF PROFILE ===';

        return implode("\n", $lines);
    }

    /**
     * Detect meal-frequency and intermittent-fasting patterns in the dietary regimen string
     * and return an explicit guidance line for the AI so it sizes calories per meal correctly.
     */
    private function interpretMealPattern(string $regimen, ?int $dailyCalories): ?string
    {
        $lower = strtolower($regimen);

        $meals = match (true) {
            str_contains($lower, 'omad'), str_contains($lower, '1mad') => 1,
            str_contains($lower, '2mad') => 2,
            str_contains($lower, '3mad') => 3,
            default => null,
        };

        if ($meals === null) {
            return null;
        }

        $ifWindow = match (true) {
            str_contains($lower, '16:8'), str_contains($lower, '16/8') => '16:8 (16 h fast, 8 h eating window)',
            str_contains($lower, '18:6'), str_contains($lower, '18/6') => '18:6 (18 h fast, 6 h eating window)',
            str_contains($lower, '20:4'), str_contains($lower, '20/4') => '20:4 (20 h fast, 4 h eating window)',
            str_contains($lower, '23:1'), str_contains($lower, '23/1') => '23:1 (23 h fast, 1 h eating window)',
            default => null,
        };

        $mealWord = $meals === 1 ? 'meal' : 'meals';
        $pct = (int) round(100 / $meals);
        $perMeal = $dailyCalories ? (int) round($dailyCalories / $meals) : null;

        $note = "IMPORTANT — MEAL PATTERN: This user eats exactly {$meals} {$mealWord} per day";
        $note .= $ifWindow ? " on a {$ifWindow} intermittent fasting protocol." : '.';
        $note .= " Each meal must supply roughly {$pct}% of their total daily nutrition";
        $note .= $perMeal
            ? " (~{$perMeal} kcal, based on their {$dailyCalories} kcal daily goal)."
            : ' — do NOT generate low-calorie token meals.';
        $note .= ' Conventional meal-size assumptions (e.g. a light 300 kcal breakfast) do not apply — every meal in their eating window must be complete and substantial.';

        return $note;
    }
}
