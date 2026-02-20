<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\ClassModel;
use App\Models\Enrollment;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $data = [
            ['name' => 'Benjamim', 'guardian' => 'Carolina', 'class' => 'KIDS', 'schedule' => '07:00/17:00', 'fee' => 1870.00],
            ['name' => 'Maitê', 'guardian' => 'Mariana', 'class' => 'BERÇÁRIO', 'schedule' => '11:00/18:00', 'fee' => 1498.00],
            ['name' => 'Liz', 'guardian' => 'Helidiane', 'class' => 'BERÇÁRIO', 'schedule' => '08:00/17:00', 'fee' => 1700.00],
            ['name' => 'Juninho', 'guardian' => 'Mikhaella', 'class' => 'BERÇÁRIO', 'schedule' => '07:00/18:00', 'fee' => 1776.50],
            ['name' => 'Joaquim', 'guardian' => 'Maria Cristina', 'class' => 'KIDS', 'schedule' => '13:00/17:00', 'fee' => 1056.00],
            ['name' => 'Maria Aurora Lopes', 'guardian' => 'Micaela', 'class' => 'MATERNAL I', 'schedule' => '07:00/17:00', 'fee' => 1605.00],
            ['name' => 'João Paulo', 'guardian' => 'Jankyelle', 'class' => 'MATERNAL I', 'schedule' => '07:00/17:00', 'fee' => 1500.00],
            ['name' => 'Ayla', 'guardian' => 'Gyvanya', 'class' => 'MATERNAL I', 'schedule' => '08:00/17:00', 'fee' => 1520.00],
            ['name' => 'Pietro', 'guardian' => 'Alessandra', 'class' => 'KIDS', 'schedule' => '11:00/17:00', 'fee' => 1439.11],
            ['name' => 'Alice Oliveira', 'guardian' => 'Bianca', 'class' => 'KIDS', 'schedule' => '12:00/19:00', 'fee' => 1444.50],
            ['name' => 'LUIZ H.', 'guardian' => 'STELLA', 'class' => 'KIDS', 'schedule' => '07:00/18:00', 'fee' => 1056.00],
            ['name' => 'Gael', 'guardian' => 'Vanessa', 'class' => 'MATERNAL I', 'schedule' => '08:00/17:00', 'fee' => 1685.00],
            ['name' => 'Rael', 'guardian' => 'Vanessa', 'class' => 'KIDS', 'schedule' => '08:00/17:00', 'fee' => 1685.00],
            ['name' => 'Bento', 'guardian' => 'Cecília', 'class' => 'BERÇÁRIO', 'schedule' => '07:00/17:30', 'fee' => 1798.00],
            ['name' => 'Gael Benjamim', 'guardian' => 'Andreza', 'class' => 'MATERNAL I', 'schedule' => '13:00/17:30', 'fee' => 1056.00],
            ['name' => 'Gabriela', 'guardian' => 'Poliana', 'class' => 'MATERNAL I', 'schedule' => '10:00/19:00', 'fee' => 1400.00],
            ['name' => 'Helena Day Use 3x', 'guardian' => 'Aranda', 'class' => 'BERÇÁRIO', 'schedule' => '07:00/17:00', 'fee' => 1480.00],
            ['name' => 'Lucas', 'guardian' => 'Thalita', 'class' => 'KIDS', 'schedule' => '13:00/19:00', 'fee' => 1300.00],
            ['name' => 'Maitê Viana', 'guardian' => 'Natalia', 'class' => 'BERÇÁRIO', 'schedule' => '09:00/17:30', 'fee' => 1870.00],
            ['name' => 'Aurora Nascimento', 'guardian' => 'Amanda', 'class' => 'MATERNAL I', 'schedule' => '07:00/19:00', 'fee' => 2086.50],
            ['name' => 'Maria Cecília', 'guardian' => 'Rayssa', 'class' => 'MATERNAL I', 'schedule' => '13:00/17:00', 'fee' => 1056.00],
            ['name' => 'Rafael', 'guardian' => 'Lisiane', 'class' => 'KIDS', 'schedule' => '11:00/18:30', 'fee' => 1475.00],
            ['name' => 'Augusto', 'guardian' => 'Adryelly', 'class' => 'BERÇÁRIO', 'schedule' => '07:00/17:30', 'fee' => 1450.00],
            ['name' => 'Mariah', 'guardian' => 'Natalia', 'class' => 'MATERNAL I', 'schedule' => '13:30/17:30', 'fee' => 850.00],
            ['name' => 'Caio Gomes', 'guardian' => 'Alice', 'class' => 'MATERNAL I', 'schedule' => '08:30/12:30', 'fee' => 1056.00],
            ['name' => 'Davi Lucca', 'guardian' => 'Débora', 'class' => 'KIDS', 'schedule' => '13:30/17:30', 'fee' => 1056.00],
            ['name' => 'Benício', 'guardian' => 'Letícia', 'class' => 'BERÇÁRIO', 'schedule' => '08:00/17:00', 'fee' => 1870.00],
            ['name' => 'Maria Elisa', 'guardian' => 'Erilis', 'class' => 'KIDS', 'schedule' => '08:00/17:00', 'fee' => 1870.00],
            ['name' => 'Maria Isabella', 'guardian' => 'Nicolly', 'class' => 'KIDS', 'schedule' => '08:00/17:00', 'fee' => 1800.00],
            ['name' => 'Yasmim Soares', 'guardian' => 'Anna Carolyne', 'class' => 'KIDS', 'schedule' => '11:00/18:00', 'fee' => 1475.00],
            ['name' => 'Gabriel Araújo', 'guardian' => 'Andreesa Lilían', 'class' => 'KIDS', 'schedule' => '07:30/16:30', 'fee' => 1870.00],
        ];

        foreach ($data as $item) {
            // Find or create Guardian
            $guardian = Guardian::firstOrCreate(
                ['name' => $item['guardian']],
                ['status' => 'active']
            );

            // Find or create Class (normalized name)
            $className = strtoupper($item['class']);
            $class = ClassModel::firstOrCreate(
                ['name' => $className],
                [
                    'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                    'start_time' => '07:00',
                    'end_time' => '18:00',
                    'status' => 'active',
                    'capacity' => 20
                ]
            );

            // Parse schedule
            $scheduleParts = explode('/', $item['schedule']);
            $startTime = trim($scheduleParts[0]);
            $endTime = isset($scheduleParts[1]) ? trim($scheduleParts[1]) : '18:00';
            
            // Fix malformed times (like 08:)
            if (strpos($startTime, ':') !== false) {
                $timeParts = explode(':', $startTime);
                $startTime = str_pad($timeParts[0], 2, '0', STR_PAD_LEFT) . ':' . str_pad($timeParts[1] ?: '00', 2, '0', STR_PAD_LEFT);
            }
            if (strpos($endTime, ':') !== false) {
                $timeParts = explode(':', $endTime);
                $endTime = str_pad($timeParts[0], 2, '0', STR_PAD_LEFT) . ':' . str_pad($timeParts[1] ?: '00', 2, '0', STR_PAD_LEFT);
            }

            // Create Student
            $student = Student::create([
                'guardian_id' => $guardian->id,
                'name' => $item['name'],
                'status' => 'active',
                'monthly_fee' => $item['fee'],
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);

            // Enroll Student
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'start_date' => Carbon::today(),
                'status' => 'active',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migrations are typically not reversed automatically
    }
};
