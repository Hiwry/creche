<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BirthdayController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $year = (int) $request->input('year', $now->year);
        $month = (int) $request->input('month', $now->month);

        if ($month < 1 || $month > 12) {
            $month = $now->month;
        }

        if ($year < 2000 || $year > $now->year + 1) {
            $year = $now->year;
        }

        $students = Student::active()
            ->whereNotNull('birth_date')
            ->whereMonth('birth_date', $month)
            ->orderByRaw('DAY(birth_date)')
            ->orderBy('name')
            ->get();

        $birthdaysByDay = $students->groupBy(function ($student) {
            return (int) $student->birth_date->format('d');
        });

        return view('reports.birthdays', [
            'year' => $year,
            'month' => $month,
            'students' => $students,
            'birthdaysByDay' => $birthdaysByDay,
        ]);
    }
}
