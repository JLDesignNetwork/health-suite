<?php

namespace App\Services;

use App\Enums\Gender;
use ValueError;

final class HealthService
{
    public const float BP_THRESHOLD = 0.15;

    /**
     * Body Mass Index in kg/m².
     */
    public function bmi(float $weightKg, float $heightCm): float
    {
        $heightM = $heightCm / 100;

        return $weightKg / ($heightM * $heightM);
    }

    /**
     * U.S. Navy Body Fat Percentage (metric formula).
     *
     * Hip circumference is required for the female calculation and ignored for males.
     */
    public function bodyFatPercent(
        Gender $gender,
        float $waist,
        float $neck,
        float $height,
        ?float $hip = null,
    ): float {
        return match ($gender) {
            Gender::Male => 495 / (1.0324 - 0.19077 * log10($waist - $neck) + 0.15456 * log10($height)) - 450,
            Gender::Female => 495 / (1.29579 - 0.35004 * log10($waist + ($hip ?? throw new ValueError('Hip measurement is required for female BFP calculation.')) - $neck) + 0.22100 * log10($height)) - 450,
        };
    }

    /**
     * Signed percentage difference of current pulse from baseline.
     *
     * Negative = below baseline, positive = above.
     */
    public function pulseDeviation(int $current, int $baseline): float
    {
        return (($current - $baseline) / $baseline) * 100;
    }

    /**
     * Per-reading variance from baseline, flagging readings outside ±15%.
     */
    public function bloodPressureVariance(
        int $currentSystolic,
        int $currentDiastolic,
        int $baselineSystolic,
        int $baselineDiastolic,
    ): BloodPressureVariance {
        $systolicPercent = (($currentSystolic - $baselineSystolic) / $baselineSystolic) * 100;
        $diastolicPercent = (($currentDiastolic - $baselineDiastolic) / $baselineDiastolic) * 100;

        $thresholdPercent = self::BP_THRESHOLD * 100;

        return new BloodPressureVariance(
            systolicPercent: $systolicPercent,
            diastolicPercent: $diastolicPercent,
            systolicExceedsThreshold: abs($systolicPercent) > $thresholdPercent,
            diastolicExceedsThreshold: abs($diastolicPercent) > $thresholdPercent,
        );
    }

    /**
     * Total change from baseline weight. Negative = loss, positive = gain.
     */
    public function weightProgress(float $current, float $baseline): float
    {
        return $current - $baseline;
    }
}
