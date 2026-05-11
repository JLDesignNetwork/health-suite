<?php

namespace App\Services;

final readonly class BloodPressureVariance
{
    public function __construct(
        public float $systolicPercent,
        public float $diastolicPercent,
        public bool $systolicExceedsThreshold,
        public bool $diastolicExceedsThreshold,
    ) {}

    public function anyExceeds(): bool
    {
        return $this->systolicExceedsThreshold || $this->diastolicExceedsThreshold;
    }
}
