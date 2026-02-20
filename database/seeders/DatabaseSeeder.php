<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentHealth;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\MonthlyFee;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@schoolhub.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        
        // Create Attendant User
        User::create([
            'name' => 'Maria Secretaria',
            'email' => 'maria@schoolhub.com',
            'password' => bcrypt('password'),
            'role' => 'attendant',
        ]);
        
        // Create Teacher User
        $teacher = User::create([
            'name' => 'Professor João',
            'email' => 'joao@schoolhub.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        
        // Create Classes
        $class1 = ClassModel::create([
            'name' => 'Ballet Infantil A',
            'description' => 'Turma de ballet para crianças de 4-6 anos',
            'teacher_id' => $teacher->id,
            'days_of_week' => ['monday', 'wednesday', 'friday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'capacity' => 15,
            'monthly_fee' => 350.00,
            'status' => 'active',
        ]);
        
        $class2 = ClassModel::create([
            'name' => 'Jazz Kids',
            'description' => 'Turma de jazz para crianças',
            'teacher_id' => $teacher->id,
            'days_of_week' => ['tuesday', 'thursday'],
            'start_time' => '10:00',
            'end_time' => '11:00',
            'capacity' => 12,
            'monthly_fee' => 400.00,
            'status' => 'active',
        ]);
        
        $class3 = ClassModel::create([
            'name' => 'Ballet Juvenil',
            'description' => 'Turma de ballet para adolescentes',
            'teacher_id' => $teacher->id,
            'days_of_week' => ['monday', 'wednesday', 'friday'],
            'start_time' => '14:00',
            'end_time' => '15:30',
            'capacity' => 20,
            'monthly_fee' => 450.00,
            'status' => 'active',
        ]);
        
        // Create sample guardians and students
        $guardians = [
            ['name' => 'Ana Souza', 'cpf' => '123.456.789-00', 'phone' => '(11) 99999-0001', 'email' => 'ana@email.com'],
            ['name' => 'Carlos Oliveira', 'cpf' => '234.567.890-11', 'phone' => '(11) 99999-0002', 'email' => 'carlos@email.com'],
            ['name' => 'Fernanda Lima', 'cpf' => '345.678.901-22', 'phone' => '(11) 99999-0003', 'email' => 'fernanda@email.com'],
        ];
        
        $students = [
            ['name' => 'Sofia Souza', 'birth_date' => '2019-05-15', 'gender' => 'F'],
            ['name' => 'Pedro Oliveira', 'birth_date' => '2018-08-22', 'gender' => 'M'],
            ['name' => 'Julia Lima', 'birth_date' => '2020-01-10', 'gender' => 'F'],
        ];
        
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        foreach ($guardians as $i => $guardianData) {
            $guardian = Guardian::create($guardianData);
            
            $student = Student::create([
                'guardian_id' => $guardian->id,
                'name' => $students[$i]['name'],
                'birth_date' => $students[$i]['birth_date'],
                'gender' => $students[$i]['gender'],
                'status' => 'active',
            ]);
            
            // Create health record
            StudentHealth::create([
                'student_id' => $student->id,
                'blood_type' => ['A+', 'B+', 'O+', 'AB+'][array_rand(['A+', 'B+', 'O+', 'AB+'])],
            ]);
            
            // Enroll in a class
            $class = [$class1, $class2, $class3][$i];
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'status' => 'active',
                'start_date' => Carbon::now()->startOfMonth(),
            ]);
            
            // Create monthly fee for current month
            MonthlyFee::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'year' => $currentYear,
                'month' => $currentMonth,
                'amount' => $class->monthly_fee,
                'status' => $i === 0 ? 'paid' : 'pending', // First one is paid
                'amount_paid' => $i === 0 ? $class->monthly_fee : 0,
                'due_date' => Carbon::now()->day(10),
            ]);
        }
        
        $this->command->info('Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Admin login credentials:');
        $this->command->info('Email: admin@schoolhub.com');
        $this->command->info('Password: password');
    }
}
