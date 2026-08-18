<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function personalInfo(): HasOne
    {
        return $this->hasOne(PersonalInfo::class);
    }

    public function lifestyleProfile(): HasOne
    {
        return $this->hasOne(LifestyleProfile::class);
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(Allergy::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(Condition::class);
    }

    public function surgeries(): HasMany
    {
        return $this->hasMany(Surgery::class);
    }

    public function familyHistory(): HasMany
    {
        return $this->hasMany(FamilyHistory::class);
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(Screening::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }
}
