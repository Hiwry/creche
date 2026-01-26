@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('students.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-left: 15px;">Novo Aluno</h1>
    </div>
</div>

<form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data">
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
        <!-- Student Data -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-graduate" style="color: #7C3AED; margin-right: 10px;"></i>
                    Dados do Aluno
                </h3>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nome Completo *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" 
                       placeholder="Nome do aluno" required>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Data de Nascimento</label>
                    <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date') }}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Sexo</label>
                    <select name="gender" class="form-control">
                        <option value="">Selecione</option>
                        <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Masculino</option>
                        <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Feminino</option>
                        <option value="O" {{ old('gender') == 'O' ? 'selected' : '' }}>Outro</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Turma</label>
                    <select name="class_id" class="form-control">
                        <option value="">Selecione uma turma</option>
                        @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }} ({{ $class->formatted_schedule }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Valor da Mensalidade (R$)</label>
                    <input type="number" name="monthly_fee" class="form-control" step="0.01" min="0" 
                           value="{{ old('monthly_fee') }}" placeholder="Ex: 500.00">
                    <small style="color: #6B7280;">Se vazio, usará o valor padrão do sistema.</small>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Foto</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
            </div>
            
            <div class="form-group">
                <label class="form-label">Observações</label>
                <textarea name="observations" class="form-control" rows="3">{{ old('observations') }}</textarea>
            </div>
        </div>
        
        <!-- Guardian Data -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user" style="color: #10B981; margin-right: 10px;"></i>
                    Responsável
                </h3>
            </div>
            
            <div class="form-group">
                <label class="form-label">Selecionar Responsável Existente</label>
                <select name="guardian_id" class="form-control" id="guardian-select">
                    <option value="">-- Cadastrar Novo Responsável --</option>
                    @foreach($guardians as $guardian)
                    <option value="{{ $guardian->id }}">{{ $guardian->name }} - {{ $guardian->formatted_phone }}</option>
                    @endforeach
                </select>
            </div>
            
            <div id="new-guardian-fields">
                <div class="form-group">
                    <label class="form-label">Nome do Responsável *</label>
                    <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}">
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">CPF</label>
                        <input type="text" name="guardian_cpf" class="form-control" value="{{ old('guardian_cpf') }}" data-cpf>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="guardian_phone" class="form-control" value="{{ old('guardian_phone') }}" data-phone>
                    </div>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" name="guardian_whatsapp" class="form-control" value="{{ old('guardian_whatsapp') }}" data-phone>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="guardian_email" class="form-control" value="{{ old('guardian_email') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
        <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Cadastrar Aluno
        </button>
    </div>
</form>

@push('scripts')
<script>
document.getElementById('guardian-select').addEventListener('change', function() {
    const fields = document.getElementById('new-guardian-fields');
    fields.style.display = this.value ? 'none' : 'block';
});
</script>
@endpush
@endsection
