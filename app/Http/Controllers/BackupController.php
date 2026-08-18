<?php

namespace App\Http\Controllers;

use App\Models\Allergy;
use App\Models\Condition;
use App\Models\FamilyHistory;
use App\Models\HealthRecord;
use App\Models\Ingredient;
use App\Models\LifestyleProfile;
use App\Models\Meal;
use App\Models\Medication;
use App\Models\PersonalInfo;
use App\Models\Profile;
use App\Models\Recipe;
use App\Models\Screening;
use App\Models\Setting;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function export(): StreamedResponse
    {
        $data = [
            'meta' => [
                'app' => 'iHealth',
                'version' => config('nativephp.version', '1.3.0'),
                'exported_at' => now()->toISOString(),
            ],
            'settings' => Setting::all(['key', 'value'])->toArray(),
            'users' => User::all()->map(fn ($u) => [
                'name' => $u->name,
                'email' => $u->email,
                'password' => $u->password,
                'profile' => Profile::withoutGlobalScopes()->where('user_id', $u->id)->first()?->toArray(),
                'personal_info' => PersonalInfo::withoutGlobalScopes()->where('user_id', $u->id)->first()?->toArray(),
                'lifestyle_profile' => LifestyleProfile::withoutGlobalScopes()->where('user_id', $u->id)->first()?->toArray(),
                'health_records' => HealthRecord::withoutGlobalScopes()->where('user_id', $u->id)->get()->toArray(),
                'meals' => Meal::withoutGlobalScopes()->where('user_id', $u->id)->get()->toArray(),
                'allergies' => Allergy::withoutGlobalScopes()->where('user_id', $u->id)->get()->toArray(),
                'conditions' => Condition::withoutGlobalScopes()->where('user_id', $u->id)->get()->toArray(),
                'surgeries' => Surgery::withoutGlobalScopes()->where('user_id', $u->id)->get()->toArray(),
                'family_history' => FamilyHistory::withoutGlobalScopes()->where('user_id', $u->id)->get()->toArray(),
                'screenings' => Screening::withoutGlobalScopes()->where('user_id', $u->id)->get()->toArray(),
                'medications' => Medication::withoutGlobalScopes()->where('user_id', $u->id)->get()->toArray(),
                'ingredients' => Ingredient::withoutGlobalScopes()->where('user_id', $u->id)->get()->toArray(),
                'recipes' => Recipe::withoutGlobalScopes()->where('user_id', $u->id)->get()->toArray(),
            ])->values()->toArray(),
        ];

        $filename = 'ihealth-backup-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(
            fn () => print json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:json', 'max:102400'],
        ]);

        $content = file_get_contents($request->file('backup_file')->getRealPath());
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ($data['meta']['app'] ?? '') !== 'iHealth') {
            return back()->with('backup_error', 'Invalid file — not a valid iHealth backup.');
        }

        DB::transaction(function () use ($data): void {
            // Settings
            foreach ($data['settings'] ?? [] as $s) {
                Setting::updateOrCreate(['key' => $s['key']], ['value' => $s['value']]);
            }

            // Users and all related data
            foreach ($data['users'] ?? [] as $ud) {
                $user = User::updateOrCreate(
                    ['email' => $ud['email']],
                    ['name' => $ud['name'], 'password' => $ud['password']],
                );

                $uid = $user->id;

                // 1:1 relations
                $this->reimport1To1(Profile::class, $uid, $ud['profile'] ?? null);
                $this->reimport1To1(PersonalInfo::class, $uid, $ud['personal_info'] ?? null);
                $this->reimport1To1(LifestyleProfile::class, $uid, $ud['lifestyle_profile'] ?? null);

                // Many relations — delete then re-insert
                $this->reimport(HealthRecord::class, $uid, $ud['health_records'] ?? []);
                $this->reimport(Meal::class, $uid, $ud['meals'] ?? []);
                $this->reimport(Allergy::class, $uid, $ud['allergies'] ?? []);
                $this->reimport(Condition::class, $uid, $ud['conditions'] ?? []);
                $this->reimport(Surgery::class, $uid, $ud['surgeries'] ?? []);
                $this->reimport(FamilyHistory::class, $uid, $ud['family_history'] ?? []);
                $this->reimport(Screening::class, $uid, $ud['screenings'] ?? []);
                $this->reimport(Medication::class, $uid, $ud['medications'] ?? []);
                $this->reimport(Ingredient::class, $uid, $ud['ingredients'] ?? []);
                $this->reimport(Recipe::class, $uid, $ud['recipes'] ?? []);
            }
        });

        return back()->with('backup_ok', 'Backup restored successfully. All data has been imported.');
    }

    /** Remove auto-managed columns and force the correct user_id. */
    private function strip(array $row, int $userId): array
    {
        unset($row['id'], $row['user_id'], $row['created_at'], $row['updated_at']);

        return $row;
    }

    /** Wipe an existing 1:1 row for a user and re-insert from the backup. */
    private function reimport1To1(string $model, int $userId, ?array $row): void
    {
        $model::withoutGlobalScopes()->where('user_id', $userId)->delete();

        if ($row) {
            $instance = new $model;
            $instance->forceFill(array_merge($this->strip($row, $userId), ['user_id' => $userId]));
            $instance->save();
        }
    }

    /** Wipe existing rows for a user and re-insert from the backup. */
    private function reimport(string $model, int $userId, array $rows): void
    {
        $model::withoutGlobalScopes()->where('user_id', $userId)->delete();
        foreach ($rows as $row) {
            $model::withoutGlobalScopes()->create(
                array_merge($this->strip($row, $userId), ['user_id' => $userId])
            );
        }
    }
}
