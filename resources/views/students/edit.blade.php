@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('students.show', $student) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-left: 15px;">Editar Aluno</h1>
    </div>
</div>

<form action="{{ route('students.update', $student) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
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
        <!-- Student Data -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Dados do Aluno</h3>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nome Completo *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $student->name) }}" required>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Data de Nascimento</label>
                    <input type="date" name="birth_date" class="form-control" 
                           value="{{ old('birth_date', $student->birth_date ? $student->birth_date->format('Y-m-d') : '') }}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Sexo</label>
                    <select name="gender" class="form-control">
                        <option value="">Selecione</option>
                        <option value="M" {{ old('gender', $student->gender) == 'M' ? 'selected' : '' }}>Masculino</option>
                        <option value="F" {{ old('gender', $student->gender) == 'F' ? 'selected' : '' }}>Feminino</option>
                        <option value="O" {{ old('gender', $student->gender) == 'O' ? 'selected' : '' }}>Outro</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ old('status', $student->status) == 'active' ? 'selected' : '' }}>Ativo</option>
                        <option value="inactive" {{ old('status', $student->status) == 'inactive' ? 'selected' : '' }}>Inativo</option>
                        <option value="suspended" {{ old('status', $student->status) == 'suspended' ? 'selected' : '' }}>Suspenso</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Valor da Mensalidade (R$)</label>
                    <input type="number" name="monthly_fee" class="form-control" step="0.01" min="0" 
                           value="{{ old('monthly_fee', $student->monthly_fee) }}" placeholder="Ex: 500.00">
                    <small style="color: #6B7280;">Se vazio, usará o valor padrão do sistema.</small>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Horário de Entrada</label>
                    <input type="time" name="start_time" class="form-control" 
                           value="{{ old('start_time', $student->start_time ? \Carbon\Carbon::parse($student->start_time)->format('H:i') : '') }}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Horário de Saída</label>
                    <input type="time" name="end_time" class="form-control" 
                           value="{{ old('end_time', $student->end_time ? \Carbon\Carbon::parse($student->end_time)->format('H:i') : '') }}">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Foto</label>
                @if($student->photo)
                <div style="margin-bottom: 10px;">
                    <img src="{{ asset('storage/' . $student->photo) }}" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover;">
                </div>
                @endif
                <input type="file" name="photo" class="form-control" accept="image/*">
            </div>
            
            <div class="form-group">
                <label class="form-label">Observações</label>
                <textarea name="observations" class="form-control" rows="3">{{ old('observations', $student->observations) }}</textarea>
            </div>
        </div>
        
        <!-- Health Data -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informações de Saúde</h3>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipo Sanguíneo</label>
                <select name="blood_type" class="form-control">
                    <option value="">Selecione</option>
                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $type)
                    <option value="{{ $type }}" {{ old('blood_type', $student->health->blood_type ?? '') == $type ? 'selected' : '' }}>
                        {{ $type }}
                    </option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Alergias</label>
                <input type="text" name="allergies" class="form-control" 
                       value="{{ old('allergies', $student->health->allergies ?? '') }}"
                       placeholder="Ex: Amendoim, Lactose">
            </div>
            
            <div class="form-group">
                <label class="form-label">Medicamentos</label>
                <textarea name="medications" class="form-control" rows="2" 
                          placeholder="Liste os medicamentos que o aluno toma">{{ old('medications', $student->health->medications ?? '') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Outras Condições / Observações</label>
                <textarea name="medical_conditions" class="form-control" rows="3" 
                          placeholder="Condições médicas, cuidados especiais, etc.">{{ old('medical_conditions', $student->health->medical_conditions ?? '') }}</textarea>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Contato de Emergência</label>
                    <input type="text" name="emergency_contact_name" class="form-control" 
                           value="{{ old('emergency_contact_name', $student->health->emergency_contact_name ?? '') }}"
                           placeholder="Nome">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Telefone Emergência</label>
                    <input type="text" name="emergency_contact_phone" class="form-control" 
                           value="{{ old('emergency_contact_phone', $student->health->emergency_contact_phone ?? '') }}"
                           placeholder="(00) 00000-0000" data-phone>
                </div>
            </div>
        </div>
    </div>
    
    <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
        <a href="{{ route('students.show', $student) }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Salvar Alterações
        </button>
    </div>
</form>
@endsection
