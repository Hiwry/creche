@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('sports.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-left: 15px;">Novo Esporte</h1>
    </div>
</div>

<form action="{{ route('sports.store') }}" method="POST">
    @csrf
    
    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Dados Básicos</h3>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nome do Esporte *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Ex: Judô, Ballet, Natação">
            </div>
            
            <div class="form-group">
                <label class="form-label">Valor da Mensalidade (R$) *</label>
                <input type="number" name="monthly_fee" class="form-control" step="0.01" min="0" value="{{ old('monthly_fee') }}" required placeholder="Ex: 100.00">
            </div>
        </div>
        
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Horários das Aulas</h3>
                <button type="button" class="btn btn-sm btn-secondary" id="add-schedule">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>
            
            <div id="schedules-container">
                @if(old('schedules'))
                    @foreach(old('schedules') as $index => $schedule)
                    <div class="schedule-item" style="border: 1px solid #E5E7EB; padding: 15px; border-radius: 8px; margin-bottom: 15px; position: relative;">
                        <button type="button" class="remove-schedule" style="position: absolute; top: 10px; right: 10px; background: none; border: none; color: #EF4444; cursor: pointer;">
                            <i class="fas fa-trash"></i>
                        </button>
                        <div class="form-group">
                            <label class="form-label">Dia da Semana</label>
                            <select name="schedules[{{$index}}][day_of_week]" class="form-control" required>
                                @foreach(['Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado', 'Domingo'] as $day)
                                    <option value="{{ $day }}" {{ $schedule['day_of_week'] == $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label">Início</label>
                                <input type="time" name="schedules[{{$index}}][start_time]" class="form-control" value="{{ $schedule['start_time'] }}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fim</label>
                                <input type="time" name="schedules[{{$index}}][end_time]" class="form-control" value="{{ $schedule['end_time'] }}" required>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
    
    <div class="form-actions">
        <a href="{{ route('sports.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Criar Esporte
        </button>
    </div>
</form>

@push('scripts')
<script>
let scheduleIndex = {{ old('schedules') ? count(old('schedules')) : 0 }};
document.getElementById('add-schedule').addEventListener('click', function() {
    const container = document.getElementById('schedules-container');
    const html = `
    <div class="schedule-item" style="border: 1px solid #E5E7EB; padding: 15px; border-radius: 8px; margin-bottom: 15px; position: relative;">
        <button type="button" class="remove-schedule" style="position: absolute; top: 10px; right: 10px; background: none; border: none; color: #EF4444; cursor: pointer;">
            <i class="fas fa-trash"></i>
        </button>
        <div class="form-group">
            <label class="form-label">Dia da Semana</label>
            <select name="schedules[${scheduleIndex}][day_of_week]" class="form-control" required>
                <option value="Segunda-feira">Segunda-feira</option>
                <option value="Terça-feira">Terça-feira</option>
                <option value="Quarta-feira">Quarta-feira</option>
                <option value="Quinta-feira">Quinta-feira</option>
                <option value="Sexta-feira">Sexta-feira</option>
                <option value="Sábado">Sábado</option>
                <option value="Domingo">Domingo</option>
            </select>
        </div>
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Início</label>
                <input type="time" name="schedules[${scheduleIndex}][start_time]" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Fim</label>
                <input type="time" name="schedules[${scheduleIndex}][end_time]" class="form-control" required>
            </div>
        </div>
    </div>`;
    
    container.insertAdjacentHTML('beforeend', html);
    scheduleIndex++;
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-schedule')) {
        e.target.closest('.schedule-item').remove();
    }
});
</script>
@endpush
@endsection
