@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('classes.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-left: 15px;">Nova Turma</h1>
    </div>
</div>

<div class="card">
    <form action="{{ route('classes.store') }}" method="POST">
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
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Nome da Turma *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" 
                       placeholder="Ex: Ballet Infantil A" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Professor</label>
                <select name="teacher_id" class="form-control">
                    <option value="">Selecione um professor</option>
                    @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Descrição</label>
            <textarea name="description" class="form-control" rows="3" 
                      placeholder="Descrição da turma...">{{ old('description') }}</textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Dias da Semana *</label>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                @foreach(\App\Models\ClassModel::DAYS_OF_WEEK as $key => $label)
                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                    <input type="checkbox" name="days_of_week[]" value="{{ $key }}"
                           {{ in_array($key, old('days_of_week', [])) ? 'checked' : '' }}>
                    {{ $label }}
                </label>
                @endforeach
            </div>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Horário Início *</label>
                <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Horário Fim *</label>
                <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}" required>
            </div>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Capacidade (alunos)</label>
                <input type="number" name="capacity" class="form-control" value="{{ old('capacity') }}" 
                       min="1" placeholder="Ex: 15">
            </div>
        </div>
        
        <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
            <a href="{{ route('classes.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Turma
            </button>
        </div>
    </form>
</div>
@endsection
