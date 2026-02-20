<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\AttendanceLog;
use App\Models\Student;

$log = AttendanceLog::find(1);
if ($log) {
    echo "Log ID: 1\n";
    echo "Student ID in Log: " . $log->student_id . "\n";
    echo "Student Name from Relation: " . ($log->student ? $log->student->name : 'None') . "\n";
    
    // Check if there are other Julia Limas
    $julias = Student::where('name', 'like', '%Julia Lima%')->get();
    echo "\nStudents matching 'Julia Lima':\n";
    foreach($julias as $j) {
        echo " - ID: {$j->id}, Name: {$j->name}\n";
    }
} else {
    echo "Log 1 not found.\n";
}
