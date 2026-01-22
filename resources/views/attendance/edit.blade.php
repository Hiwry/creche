@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Editar Registro de Frequência</h1>
        <span style="color: #6B7280; margin-left: 10px;">
            {{ \Carbon\Carbon::parse($log->date)->format('d/m/Y') }} - {{ $log->student->name }}
        </span>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('attendance.index', ['date' => $log->date]) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Detalhes da Frequência</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('attendance.update', $log->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Aluno</label>
                <div style="font-size: 1.1rem; font-weight: 500;">{{ $log->student->name }}</div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Horário Entrada</label>
                    <input type="time" name="check_in" class="form-control" value="{{ $log->check_in ? \Carbon\Carbon::parse($log->check_in)->format('H:i') : '' }}">
                </div>
                
                <div class="form-group">
                    <label>Horário Saída</label>
                    <input type="time" name="check_out" class="form-control" value="{{ $log->check_out ? \Carbon\Carbon::parse($log->check_out)->format('H:i') : '' }}">
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label>Retirado por</label>
                <input type="text" name="picked_up_by" class="form-control" value="{{ $log->picked_up_by }}" placeholder="Nome do responsável">
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label>Observações</label>
                <textarea name="notes" class="form-control" rows="3">{{ $log->notes }}</textarea>
            </div>
            
            <div class="info-box" style="margin-top: 20px; padding: 15px; background-color: #F3F4F6; border-radius: 8px;">
                <h4 style="font-size: 0.9rem; font-weight: 600; margin-bottom: 10px;">Cálculo de Extras (Personalizado)</h4>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Minutos Extras</label>
                        <input type="number" name="extra_minutes" class="form-control" value="{{ $log->extra_minutes }}">
                    </div>
                    <div class="form-group">
                        <label>Valor Extra (R$)</label>
                        <input type="number" step="0.01" name="extra_charge" class="form-control" value="{{ $log->extra_charge }}">
                    </div>
                </div>
                <div style="margin-top: 10px; font-size: 0.8rem; color: #6B7280;">
                    Horário Previsto: {{ \Carbon\Carbon::parse($log->expected_start)->format('H:i') }} - {{ \Carbon\Carbon::parse($log->expected_end)->format('H:i') }}
                </div>
            </div>
            
            <div class="form-actions" style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>
@endsection
