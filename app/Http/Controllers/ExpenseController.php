<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        
        $expenses = Expense::ofMonth($year, $month)
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->orderBy('expense_date', 'desc')
            ->paginate(20);
        
        $monthlyTotal = Expense::getMonthlyTotal($year, $month);
        $byCategory = Expense::getByCategory($year, $month);
        
        return view('expenses.index', compact('expenses', 'year', 'month', 'monthlyTotal', 'byCategory'));
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create()
    {
        return view('expenses.create');
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(Expense::CATEGORIES)),
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', array_keys(Expense::PAYMENT_METHODS)),
            'notes' => 'nullable|string',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $validated['user_id'] = auth()->id();

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }

        Expense::create($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Despesa registrada com sucesso!');
    }

    /**
     * Show the form for editing an expense.
     */
    public function edit(Expense $expense)
    {
        return view('expenses.edit', compact('expense'));
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(Expense::CATEGORIES)),
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', array_keys(Expense::PAYMENT_METHODS)),
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Despesa atualizada com sucesso!');
    }

    /**
     * Remove the specified expense.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Despesa excluída com sucesso!');
    }

    /**
     * Quick add expense (AJAX).
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['expense_date'] = Carbon::now();
        $validated['payment_method'] = 'cash';

        Expense::create($validated);

        return redirect()->back()->with('success', 'Despesa adicionada!');
    }
}
