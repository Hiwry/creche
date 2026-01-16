@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-left: 15px;">Nova Despesa</h1>
    </div>
</div>

<div class="card" style="max-width: 600px;">
    <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <div class="form-group">
            <label class="form-label">Descrição *</label>
            <input type="text" name="description" class="form-control" value="{{ old('description') }}" 
                   placeholder="Ex: Compra de materiais" required>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Categoria *</label>
                <select name="category" class="form-control" required>
                    <option value="">Selecione</option>
                    @foreach(\App\Models\Expense::CATEGORIES as $key => $label)
                    <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Valor (R$) *</label>
                <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" 
                       step="0.01" min="0.01" placeholder="0,00" required>
            </div>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Data *</label>
                <input type="date" name="expense_date" class="form-control" 
                       value="{{ old('expense_date', date('Y-m-d')) }}" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Forma de Pagamento *</label>
                <select name="payment_method" class="form-control" required>
                    @foreach(\App\Models\Expense::PAYMENT_METHODS as $key => $label)
                    <option value="{{ $key }}" {{ old('payment_method', 'cash') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Comprovante (opcional)</label>
            <input type="file" name="receipt" class="form-control" accept="image/*,.pdf">
        </div>
        
        <div class="form-group">
            <label class="form-label">Observações (opcional)</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Detalhes adicionais...">{{ old('notes') }}</textarea>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
            <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-save"></i> Registrar Despesa
            </button>
        </div>
    </form>
</div>
@endsection
