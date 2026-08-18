<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'blood_type',
    'pronouns',
    'emergency_contact_1_name',
    'emergency_contact_1_relationship',
    'emergency_contact_1_phone',
    'emergency_contact_2_name',
    'emergency_contact_2_relationship',
    'emergency_contact_2_phone',
    'primary_care_physician',
    'pcp_phone',
    'insurance_provider',
    'insurance_member_id',
    'insurance_group_number',
    'insurance_phone',
    'patient_notes',
])]
class PersonalInfo extends Model
{
    use BelongsToAuthUser, HasFactory;

    protected $table = 'personal_info';
}
