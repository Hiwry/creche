@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('students.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-left: 15px;">{{ $student->name }}</h1>
        <span class="badge badge-{{ $student->status_color }}" style="margin-left: 10px;">
            {{ $student->status_label }}
        </span>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('students.edit', $student) }}" class="btn btn-warning">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>
</div>

<div class="grid grid-2">
    <!-- Student Info -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-graduate" style="color: #7C3AED; margin-right: 10px;"></i>
                Dados do Aluno
            </h3>
        </div>
        
        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <img src="{{ $student->photo ? asset('storage/' . $student->photo) : 'https://ui-avatars.com/api/?name=' . urlencode($student->name) . '&size=100&background=7C3AED&color=fff' }}" 
                 style="width: 100px; height: 100px; border-radius: 12px; object-fit: cover;">
            <div>
                <h2 style="font-size: 1.25rem; margin-bottom: 5px;">{{ $student->name }}</h2>
                @if($student->age)
                <p style="color: #6B7280;">{{ $student->age }} anos</p>
                @endif
                @if($student->birth_date)
                <p style="color: #6B7280; font-size: 0.9rem;">
                    Nascimento: {{ $student->birth_date->format('d/m/Y') }}
                </p>
                @endif
            </div>
        </div>
        
        @if($student->activeEnrollments->count() > 0)
        <div style="margin-bottom: 15px;">
            <span style="font-size: 0.85rem; color: #6B7280;">Turmas:</span>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 5px;">
                @foreach($student->activeEnrollments as $enrollment)
                <span class="badge badge-info">{{ $enrollment->classModel->name ?? '-' }}</span>
                @endforeach
            </div>
        </div>
        @endif
        
        @if($student->observations)
        <div>
            <span style="font-size: 0.85rem; color: #6B7280;">Observações:</span>
            <p style="margin-top: 5px;">{{ $student->observations }}</p>
        </div>
        @endif
    </div>
    
    <!-- Guardian Info -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user" style="color: #10B981; margin-right: 10px;"></i>
                Responsável
            </h3>
        </div>
        
        @if($student->guardian)
        <div style="display: grid; gap: 15px;">
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Nome:</span>
                <div style="font-weight: 500;">{{ $student->guardian->name }}</div>
            </div>
            
            @if($student->guardian->phone)
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Telefone:</span>
                <div>{{ $student->guardian->formatted_phone }}</div>
            </div>
            @endif
            
            @if($student->guardian->whatsapp)
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">WhatsApp:</span>
                <div>{{ $student->guardian->whatsapp }}</div>
            </div>
            @endif
            
            @if($student->guardian->email)
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">E-mail:</span>
                <div>{{ $student->guardian->email }}</div>
            </div>
            @endif
            
            @if($student->guardian->cpf)
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">CPF:</span>
                <div>{{ $student->guardian->formatted_cpf }}</div>
            </div>
            @endif
        </div>
        @else
        <p style="color: #9CA3AF;">Nenhum responsável cadastrado</p>
        @endif
    </div>
</div>

<!-- Financial Summary -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-dollar-sign" style="color: #F59E0B; margin-right: 10px;"></i>
            Mensalidades Recentes
        </h3>
        <a href="{{ route('financial.index', ['search' => $student->name]) }}" class="btn btn-secondary btn-sm">
            Ver Todas
        </a>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Referência</th>
                    <th>Valor</th>
                    <th>Pago</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($student->monthlyFees->take(6) as $fee)
                <tr>
                    <td>{{ $fee->reference }}</td>
                    <td>R$ {{ number_format($fee->net_amount, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($fee->amount_paid, 2, ',', '.') }}</td>
                    <td>
                        <span class="badge badge-{{ $fee->status_color }}">{{ $fee->status_label }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #9CA3AF;">
                        Nenhuma mensalidade registrada
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    <!-- Health Info -->
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-heartbeat" style="color: #EF4444; margin-right: 10px;"></i>
                Informações de Saúde
            </h3>
        </div>
        
        <div class="grid grid-3" style="margin-bottom: 20px;">
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Tipo Sanguíneo:</span>
                <div style="font-weight: 500;">{{ $student->health->blood_type ?? '-' }}</div>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Plano de Saúde:</span>
                <div style="font-weight: 500;">{{ $student->health->health_plan_name ?? 'Não informado' }}</div>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Nº do Plano:</span>
                <div style="font-weight: 500;">{{ $student->health->health_plan_number ?? '-' }}</div>
            </div>
        </div>

        <div class="grid grid-2">
            @if($student->health && $student->health->allergies)
            <div class="alert alert-danger" style="margin: 0;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Alergias:</strong> {{ $student->health->allergies }}
            </div>
            @endif
            
            @if($student->health && $student->health->dietary_restrictions)
            <div class="alert alert-warning" style="margin: 0;">
                <i class="fas fa-utensils"></i>
                <strong>Restrições Alimentares:</strong> {{ $student->health->dietary_restrictions }}
            </div>
            @endif

            @if($student->health && $student->health->medical_conditions)
            <div class="alert alert-info" style="margin: 0; margin-top: 10px; grid-column: 1 / -1;">
                <i class="fas fa-notes-medical"></i>
                <strong>Condições e Observações:</strong> {{ $student->health->medical_conditions }}
            </div>
            @endif
        </div>

        <div style="margin-top: 20px;">
            <span style="font-size: 0.85rem; color: #6B7280;">Contato de Emergência:</span>
            <div style="font-weight: 500;">{{ $student->health->emergency_contact_name ?? '-' }} {{ $student->health->emergency_contact_phone ? ' - ' . $student->health->emergency_contact_phone : '' }}</div>
        </div>
    </div>

    <!-- Documents -->
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-file-alt" style="color: #3B82F6; margin-right: 10px;"></i>
                Documentos e Imagens
            </h3>
            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('upload-modal').style.display='block'">
                <i class="fas fa-upload"></i> Upload
            </button>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($student->documents as $doc)
                    <tr>
                        <td>{{ $doc->name }}</td>
                        <td>{{ $doc->type_label }}</td>
                        <td>{{ $doc->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="{{ asset('storage/' . $doc->path) }}" target="_blank" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form action="{{ route('students.documents.delete', [$student, $doc]) }}" method="POST" onsubmit="return confirm('Excluir documento?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center">Nenhum documento</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="modal" style="display:none; position:fixed; z-index:100; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
        <div style="background:#fff; margin:10% auto; padding:20px; width:400px; border-radius:8px;">
            <h3>Fazer Upload de Documento</h3>
            <form action="{{ route('students.documents.upload', $student) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-top:15px;">
                    <label>Título do Documento</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group" style="margin-top:15px;">
                    <label>Tipo</label>
                    <select name="type" class="form-control" required>
                        @foreach(App\Models\StudentDocument::TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-top:15px;">
                    <label>Arquivo</label>
                    <input type="file" name="document" class="form-control" required>
                </div>
                <div style="margin-top:20px; text-align:right;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('upload-modal').style.display='none'">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Materials and Extra Hours -->
    <div class="grid grid-2" style="margin-top: 20px;">
        <!-- School Materials Checklist -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list-check" style="color: #6366F1; margin-right: 10px;"></i>
                    Materiais Escolares
                </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('school-materials.student-checklist.update', $student) }}" method="POST">
                    @csrf
                    <div style="display: grid; gap: 10px;">
                        @forelse($allMaterials as $material)
                        @php
                            $studentMaterial = $student->studentMaterials->where('material_id', $material->id)->first();
                            $checked = $studentMaterial && $studentMaterial->received;
                        @endphp
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 8px; border-radius: 6px; background: #F9FAFB;">
                            <input type="hidden" name="materials[{{ $material->id }}]" value="0">
                            <input type="checkbox" name="materials[{{ $material->id }}]" value="1" {{ $checked ? 'checked' : '' }}>
                            <span>{{ $material->name }}</span>
                            @if($studentMaterial && $studentMaterial->received_at)
                                <span style="font-size: 0.75rem; color: #9CA3AF; margin-left: auto;">
                                    {{ $studentMaterial->received_at->format('d/m/Y') }}
                                </span>
                            @endif
                        </label>
                        @empty
                        <p style="color: #9CA3AF;">Nenhum material cadastrado</p>
                        @endforelse
                    </div>
                    @if($allMaterials->count() > 0)
                    <button type="submit" class="btn btn-primary btn-sm" style="margin-top: 15px; width: 100%;">
                        Salvar Checklist
                    </button>
                    @endif
                </form>
            </div>
        </div>

        <!-- Extra Hours Summary -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history" style="color: #F59E0B; margin-right: 10px;"></i>
                    Histórico de Presença
                </h3>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="badge badge-warning">Extra Total: R$ {{ number_format($extraHoursSummary, 2, ',', '.') }}</span>
                    <a href="{{ route('attendance.extra-hours', ['student_id' => $student->id]) }}" class="btn btn-sm btn-secondary" title="Ver relatório detalhado">
                        <i class="fas fa-expand-alt"></i> Expandir
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($student->attendanceLogs as $log)
                            <tr class="{{ $log->trashed() ? 'text-muted' : '' }}" style="{{ $log->trashed() ? 'background-color: #f9fafb;' : '' }}">
                                <td>
                                    {{ $log->date->format('d/m/Y') }}
                                    @if($log->trashed())
                                        <br><span class="badge badge-secondary" style="font-size: 0.7em;">Reiniciado</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->check_in)
                                        <span class="badge {{ $log->trashed() ? 'badge-secondary' : 'badge-success' }}">{{ \Carbon\Carbon::parse($log->check_in)->format('H:i') }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($log->check_out)
                                        <span class="badge {{ $log->trashed() ? 'badge-secondary' : 'badge-primary' }}">{{ \Carbon\Carbon::parse($log->check_out)->format('H:i') }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($log->extra_minutes > 0)
                                        <span style="color: {{ $log->trashed() ? '#9CA3AF' : '#ca8a04' }}; font-weight: bold;">+{{ $log->extra_minutes }}min</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center">Nenhum registro encontrado</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="{{ route('attendance.index', ['search' => $student->name]) }}" class="btn btn-secondary btn-sm">
                        Registrar no Diário
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
