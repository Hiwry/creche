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
                    <label class="form-label">Valor da Mensalidade (R$) *</label>
                    <input type="number" name="monthly_fee" class="form-control" step="0.01" min="0" 
                           value="{{ old('monthly_fee', $student->monthly_fee) }}" placeholder="Ex: 500.00" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Dia de Vencimento *</label>
                    <input type="number" name="due_day" class="form-control" min="1" max="31" 
                           value="{{ old('due_day', $student->due_day) }}" placeholder="Ex: 10" required>
                    <small style="color: #6B7280;">Dia do mês para vencimento da fatura</small>
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
                <label class="form-label">Endereço</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $student->address) }}" placeholder="Rua, número, bairro...">
            </div>

            <div class="form-group">
                <label class="form-label">Observações</label>
                <textarea name="observations" class="form-control" rows="3">{{ old('observations', $student->observations) }}</textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Pessoas Autorizadas a Buscar</span>
                    <button type="button" class="btn btn-sm btn-secondary" id="add-auth-person" style="font-size: 0.8rem;">
                        <i class="fas fa-plus"></i> Adicionar
                    </button>
                </label>
                <div id="authorized-persons-container">
                    @php
                        $authPersons = old('authorized_persons') ?? $student->authorizedPersons->map(function($p) {
                            return [
                                'id' => $p->id,
                                'name' => $p->name,
                                'cpf' => $p->cpf,
                                'phone' => $p->phone,
                                'whatsapp' => $p->whatsapp,
                            ];
                        })->toArray();
                    @endphp
                    
                    @if($authPersons)
                        @foreach($authPersons as $index => $person)
                        <div class="auth-person-item" style="border: 1px solid #E5E7EB; padding: 15px; border-radius: 8px; margin-bottom: 15px; position: relative;">
                            <button type="button" class="remove-auth-person" style="position: absolute; top: 10px; right: 10px; background: none; border: none; color: #EF4444; cursor: pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                            @if(isset($person['id']))
                                <input type="hidden" name="authorized_persons[{{$index}}][id]" value="{{ $person['id'] }}">
                            @endif
                            <div class="form-group">
                                <label class="form-label">Nome *</label>
                                <input type="text" name="authorized_persons[{{$index}}][name]" class="form-control" value="{{ $person['name'] ?? '' }}" required>
                            </div>
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">CPF</label>
                                    <input type="text" name="authorized_persons[{{$index}}][cpf]" class="form-control" value="{{ $person['cpf'] ?? '' }}" data-cpf>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Telefone</label>
                                    <input type="text" name="authorized_persons[{{$index}}][phone]" class="form-control" value="{{ $person['phone'] ?? '' }}" data-phone>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">WhatsApp</label>
                                <input type="text" name="authorized_persons[{{$index}}][whatsapp]" class="form-control" value="{{ $person['whatsapp'] ?? '' }}" data-phone>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
                <small style="color: #6B7280;">Adicione as pessoas que podem buscar o aluno (além do responsável).</small>
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

        <!-- Guardian Data -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user" style="color: #10B981; margin-right: 10px;"></i>
                    Dados do Responsável
                </h3>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nome do Responsável *</label>
                <input type="text" name="guardian_name" class="form-control" 
                       value="{{ old('guardian_name', $student->guardian->name ?? '') }}" required>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">CPF</label>
                    <input type="text" name="guardian_cpf" class="form-control" 
                           value="{{ old('guardian_cpf', $student->guardian->cpf ?? '') }}" data-cpf>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="guardian_phone" class="form-control" 
                           value="{{ old('guardian_phone', $student->guardian->phone ?? '') }}" data-phone>
                </div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="guardian_whatsapp" class="form-control" 
                           value="{{ old('guardian_whatsapp', $student->guardian->whatsapp ?? '') }}" data-phone>
                </div>
                
                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="guardian_email" class="form-control" 
                           value="{{ old('guardian_email', $student->guardian->email ?? '') }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Endereço</label>
                <input type="text" name="guardian_address" class="form-control"
                       value="{{ old('guardian_address', $student->guardian->address ?? '') }}"
                       placeholder="Rua, avenida, etc.">
            </div>

            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="guardian_city" class="form-control"
                           value="{{ old('guardian_city', $student->guardian->city ?? '') }}"
                           placeholder="Cidade">
                </div>
                
                <div class="form-group">
                    <label class="form-label">UF</label>
                    <input type="text" name="guardian_state" class="form-control"
                           value="{{ old('guardian_state', $student->guardian->state ?? '') }}"
                           placeholder="AL">
                </div>

                <div class="form-group">
                    <label class="form-label">CEP</label>
                    <input type="text" name="guardian_cep" class="form-control"
                           value="{{ old('guardian_cep', $student->guardian->cep ?? '') }}"
                           placeholder="00000-000">
                </div>
            </div>
        </div>
    </div>
    
    <div class="form-actions">
        <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Salvar Alterações
        </button>
    </div>
</form>
@push('scripts')
<script>
let personIndex = {{ isset($authPersons) ? count($authPersons) : 0 }};
document.getElementById('add-auth-person').addEventListener('click', function() {
    const container = document.getElementById('authorized-persons-container');
    const html = `
    <div class="auth-person-item" style="border: 1px solid #E5E7EB; padding: 15px; border-radius: 8px; margin-bottom: 15px; position: relative;">
        <button type="button" class="remove-auth-person" onclick="this.parentElement.remove()" style="position: absolute; top: 10px; right: 10px; background: none; border: none; color: #EF4444; cursor: pointer;">
            <i class="fas fa-trash"></i>
        </button>
        <div class="form-group">
            <label class="form-label">Nome *</label>
            <input type="text" name="authorized_persons[${personIndex}][name]" class="form-control" required placeholder="Nome completo">
        </div>
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">CPF</label>
                <input type="text" name="authorized_persons[${personIndex}][cpf]" class="form-control" data-cpf placeholder="000.000.000-00">
            </div>
            <div class="form-group">
                <label class="form-label">Telefone</label>
                <input type="text" name="authorized_persons[${personIndex}][phone]" class="form-control" data-phone placeholder="(00) 00000-0000">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">WhatsApp</label>
            <input type="text" name="authorized_persons[${personIndex}][whatsapp]" class="form-control" data-phone placeholder="(00) 00000-0000">
        </div>
    </div>`;
    
    container.insertAdjacentHTML('beforeend', html);
    personIndex++;
});

// Event delegation for removal
document.getElementById('authorized-persons-container').addEventListener('click', function(e) {
    if (e.target.closest('.remove-auth-person')) {
        e.target.closest('.auth-person-item').remove();
    }
});
</script>
@endpush
@endsection
