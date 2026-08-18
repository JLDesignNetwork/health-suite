<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\Meal;
use Illuminate\Http\Response;

final class HistoryExportController extends Controller
{
    public function __invoke(): Response
    {
        $records = HealthRecord::orderBy('date', 'desc')->get();
        $dates = $records->pluck('date')->map(fn ($d) => $d->toDateString())->all();
        $meals = Meal::where(function ($q) use ($dates): void {
            foreach ($dates as $date) {
                $q->orWhereDate('date', $date);
            }
        })
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy(fn ($m) => $m->date->toDateString())
            ->map(fn ($group) => $group->values());

        $out = fopen('php://temp', 'w');

        fputcsv($out, [
            'Date', 'Weight (kg)', 'Neck (cm)', 'Waist (cm)', 'Hip (cm)',
            'Systolic', 'Diastolic', 'Pulse', 'Water (L)', 'Exercise (min)',
            'Meal Type', 'Description', 'Calories (kcal)',
        ]);

        foreach ($records as $record) {
            $date = $record->date->toDateString();
            $dayMeals = ($meals[$date] ?? collect())->values();
            $rows = max(1, $dayMeals->count());

            for ($i = 0; $i < $rows; $i++) {
                $meal = $dayMeals[$i] ?? null;
                fputcsv($out, [
                    $i === 0 ? $date : '',
                    $i === 0 ? $record->weight : '',
                    $i === 0 ? $record->neck : '',
                    $i === 0 ? $record->waist : '',
                    $i === 0 ? $record->hip : '',
                    $i === 0 ? $record->systolic : '',
                    $i === 0 ? $record->diastolic : '',
                    $i === 0 ? $record->pulse : '',
                    $i === 0 ? $record->water_intake_l : '',
                    $i === 0 ? $record->exercise_minutes : '',
                    $meal?->meal_type?->label() ?? '',
                    $meal?->description ?? '',
                    $meal?->calories ?? '',
                ]);
            }
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="ihealth-export-'.today()->toDateString().'.csv"',
        ]);
    }
}
