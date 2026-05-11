<?php

use App\Enums\Gender;
use App\Services\BloodPressureVariance;
use App\Services\HealthService;

beforeEach(function (): void {
    $this->service = new HealthService;
});

describe('bmi', function (): void {
    it('computes BMI from weight in kg and height in cm', function (): void {
        // 70 kg, 175 cm → 70 / 1.75² = 22.857...
        expect($this->service->bmi(70.0, 175.0))
            ->toBeFloat()
            ->toEqualWithDelta(22.857, 0.001);
    });

    it('handles fractional weights', function (): void {
        // 82.5 kg, 180 cm → 82.5 / 1.8² = 25.46296...
        expect($this->service->bmi(82.5, 180.0))
            ->toEqualWithDelta(25.463, 0.001);
    });

    it('returns the same value regardless of input precision', function (): void {
        $a = $this->service->bmi(70.0, 175.0);
        $b = $this->service->bmi(70.00, 175.00);

        expect($a)->toBe($b);
    });
});

describe('bodyFatPercent', function (): void {
    it('computes male BFP via the metric U.S. Navy formula', function (): void {
        // waist=85cm, neck=38cm, height=175cm
        // 495 / (1.0324 - 0.19077*log10(47) + 0.15456*log10(175)) - 450 ≈ 16.94
        expect($this->service->bodyFatPercent(Gender::Male, waist: 85.0, neck: 38.0, height: 175.0))
            ->toEqualWithDelta(16.94, 0.05);
    });

    it('computes female BFP via the metric U.S. Navy formula', function (): void {
        // waist=80cm, hip=100cm, neck=33cm, height=165cm
        // 495 / (1.29579 - 0.35004*log10(147) + 0.22100*log10(165)) - 450 ≈ 31.86
        expect($this->service->bodyFatPercent(Gender::Female, waist: 80.0, neck: 33.0, height: 165.0, hip: 100.0))
            ->toEqualWithDelta(31.86, 0.05);
    });

    it('throws when hip is missing for female calculation', function (): void {
        $this->service->bodyFatPercent(Gender::Female, waist: 80.0, neck: 33.0, height: 165.0);
    })->throws(ValueError::class, 'Hip measurement is required for female BFP calculation.');

    it('ignores hip for male calculation', function (): void {
        $withHip = $this->service->bodyFatPercent(Gender::Male, waist: 85.0, neck: 38.0, height: 175.0, hip: 100.0);
        $withoutHip = $this->service->bodyFatPercent(Gender::Male, waist: 85.0, neck: 38.0, height: 175.0);

        expect($withHip)->toBe($withoutHip);
    });
});

describe('pulseDeviation', function (): void {
    it('returns a positive percent when current exceeds baseline', function (): void {
        // (85 - 70) / 70 * 100 = 21.428...
        expect($this->service->pulseDeviation(current: 85, baseline: 70))
            ->toEqualWithDelta(21.43, 0.01);
    });

    it('returns a negative percent when current is below baseline', function (): void {
        // (60 - 70) / 70 * 100 = -14.285...
        expect($this->service->pulseDeviation(current: 60, baseline: 70))
            ->toEqualWithDelta(-14.29, 0.01);
    });

    it('returns zero when current equals baseline', function (): void {
        expect($this->service->pulseDeviation(current: 70, baseline: 70))
            ->toBe(0.0);
    });
});

describe('bloodPressureVariance', function (): void {
    it('flags only systolic when it crosses 15% from baseline', function (): void {
        // 140/90 vs baseline 120/80
        //   systolic: (140-120)/120 = 16.67% > 15% → flagged
        //   diastolic: (90-80)/80 = 12.5% < 15% → not flagged
        $variance = $this->service->bloodPressureVariance(
            currentSystolic: 140,
            currentDiastolic: 90,
            baselineSystolic: 120,
            baselineDiastolic: 80,
        );

        expect($variance)->toBeInstanceOf(BloodPressureVariance::class)
            ->and($variance->systolicPercent)->toEqualWithDelta(16.67, 0.01)
            ->and($variance->diastolicPercent)->toEqualWithDelta(12.5, 0.01)
            ->and($variance->systolicExceedsThreshold)->toBeTrue()
            ->and($variance->diastolicExceedsThreshold)->toBeFalse()
            ->and($variance->anyExceeds())->toBeTrue();
    });

    it('does not flag readings within ±15% of baseline', function (): void {
        // 125/82 vs 120/80 → +4.17% / +2.5% — both within range
        $variance = $this->service->bloodPressureVariance(125, 82, 120, 80);

        expect($variance->systolicExceedsThreshold)->toBeFalse()
            ->and($variance->diastolicExceedsThreshold)->toBeFalse()
            ->and($variance->anyExceeds())->toBeFalse();
    });

    it('flags low readings symmetrically (negative deviation > 15%)', function (): void {
        // 100/60 vs 120/80 → -16.67% / -25% — both flagged
        $variance = $this->service->bloodPressureVariance(100, 60, 120, 80);

        expect($variance->systolicPercent)->toEqualWithDelta(-16.67, 0.01)
            ->and($variance->diastolicPercent)->toEqualWithDelta(-25.0, 0.01)
            ->and($variance->systolicExceedsThreshold)->toBeTrue()
            ->and($variance->diastolicExceedsThreshold)->toBeTrue();
    });
});

describe('weightProgress', function (): void {
    it('returns a negative number when current is below baseline (loss)', function (): void {
        expect($this->service->weightProgress(current: 72.0, baseline: 80.0))
            ->toBe(-8.0);
    });

    it('returns a positive number when current is above baseline (gain)', function (): void {
        expect($this->service->weightProgress(current: 82.0, baseline: 80.0))
            ->toBe(2.0);
    });

    it('returns zero when current equals baseline', function (): void {
        expect($this->service->weightProgress(80.0, 80.0))->toBe(0.0);
    });
});
