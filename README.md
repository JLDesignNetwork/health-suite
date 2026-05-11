# iHealth Master Project Specification (V4.0 Consolidated)

## 1. Project Overview & Objective
"iHealth" is a secure, multi-user Laravel application designed to replace the [Health Diary](https://docs.google.com/spreadsheets/d/1nleopHDxvP24mhxThWbcSppXdkCXN15ACCXQwwHBqDE/edit?gid=1418125343#gid=1418125343) spreadsheet. The system focuses on **individualized health monitoring**, establishing personal baselines to track progress and physiological deviations relative to the specific user rather than generic medical averages.

---

## 2. Mandatory "Entry Stats" & Onboarding
Users must complete a "Baseline Profile" before accessing the dashboard. A middleware `EnsureProfileComplete` should enforce this.

### **The Baseline (Personal Benchmark)**
* **Biometrics:** Gender (Male/Female), Date of Birth, Height (cm).
* **Starting Measurements:** Starting Weight (kg), Neck (cm), Waist (cm), Hip (cm).
* **Physiological Norms:** * `baseline_resting_heart_rate`: The user's specific "normal" pulse.
    * `baseline_blood_pressure`: Systolic and Diastolic "normal" range.

---

## 3. Database Schema (Unified Architecture)

### **Profiles Table**
Stores the immutable (or versioned) benchmarks and static user data.
* `user_id` (foreignId), `gender` (enum), `height_cm`, `dob`.
* `baseline_weight`, `baseline_pulse`, `baseline_systolic`, `baseline_diastolic`.
* **Goals:** `target_weight`, `daily_calorie_goal`, `daily_water_goal`, `weekly_exercise_goal`.

### **HealthRecords Table (Daily Logs)**
* `user_id`, `date`.
* `weight`, `neck`, `waist`, `hip`.
* `systolic`, `diastolic`, `pulse`.
* `water_intake_l`, `exercise_minutes`.

### **Meals Table (Dietary Tracking)**
* `user_id`, `date`, `meal_type` (Breakfast, Lunch, Dinner, Snack).
* `description`, `calories` (integer).

---

## 4. Core Logic & Health Service
All calculations must be handled in a dedicated `HealthService` class.

### **Standard Formulas**
* **BMI:** $$\text{Weight} / (\text{Height}/100)^2$$
* **U.S. Navy Body Fat Percentage (BFP):**
    * **Male:** $$495 / (1.0324 - 0.19077 \times \log_{10}(\text{waist} - \text{neck}) + 0.15456 \times \log_{10}(\text{height})) - 450$$
    * **Female:** $$495 / (1.29579 - 0.35004 \times \log_{10}(\text{waist} + \text{hip} - \text{neck}) + 0.22100 \times \log_{10}(\text{height})) - 450$$

### **Individualized Physiology Logic**
* **Pulse Deviation:** Calculate percentage difference: $$((\text{CurrentPulse} - \text{BaselinePulse}) / \text{BaselinePulse}) \times 100$$.
* **BP Variance:** Compare current readings against the `baseline_blood_pressure`. Highlight fluctuations that exceed a 15% threshold from the user's specific normal.
* **Weight Progress:** Always track "Total Loss" as `Current Weight - Baseline Weight`.

---

## 5. Privacy & Access Control
* **Multi-Tenancy:** Use a Global Scope on all models (`Profile`, `HealthRecord`, `Meal`) to ensure data is strictly filtered by `auth()->id()`.
* **Profile Isolation:** Users (e.g., Husband and Wife) have completely independent accounts with no visibility into the other's stats, baselines, or goals.

---

## 6. UI/UX: The "Clean Look" Dashboard

### **Visual Progress**
* **Daily Goal Rings:** Circular progress bars for Calorie Intake (remaining/over), Water, and Exercise minutes.
* **Goal Lines:** Charts must include a static horizontal "Goal Line" (e.g., Target Weight) to show progress over time.

### **The "Clean" History Table**
* **Frontend Logic:** To prevent the "merged cell" issue that breaks charts, the backend must provide a date for every row. 
* **Visual Grouping:** The UI should hide the date text for subsequent entries on the same day (e.g., using a conditional `hidden` class if `current_row.date == previous_row.date`), creating a clean, single-date appearance without actually merging cells or deleting data.

---

## 7. Developer AI Prompt (For PhpStorm/Cursor)
> "Generate a Laravel 11 application for 'iHealth' based on the V4.0 Master Specification. 
> 1. Create Migrations for Profiles, HealthRecords, and Meals with all baseline/goal fields. 
> 2. Implement a 'HealthService' using the Navy BFP and BMI formulas. 
> 3. Add logic for 'Pulse Deviation' that compares current entries to the user's specific baseline. 
> 4. Build an Onboarding Wizard to capture 'Entry Stats' before allowing dashboard access. 
> 5. Use Tailwind/Filament for the UI, ensuring the 'Weight History' table visually hides duplicate dates for a clean look while maintaining full data integrity for charts. 
> 6. Scope all queries strictly to the authenticated user."
