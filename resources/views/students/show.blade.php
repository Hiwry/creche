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
@if($student->health)
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-heartbeat" style="color: #EF4444; margin-right: 10px;"></i>
            Informações de Saúde
        </h3>
    </div>
    
    <div class="grid grid-3">
        @if($student->health->blood_type)
        <div>
            <span style="font-size: 0.85rem; color: #6B7280;">Tipo Sanguíneo:</span>
            <div style="font-weight: 500;">{{ $student->health->blood_type }}</div>
        </div>
        @endif
        
        @if($student->health->allergies)
        <div>
            <span style="font-size: 0.85rem; color: #6B7280;">Alergias:</span>
            <div style="color: #EF4444; font-weight: 500;">{{ $student->health->allergies }}</div>
        </div>
        @endif
        
        @if($student->health->medications)
        <div>
            <span style="font-size: 0.85rem; color: #6B7280;">Medicamentos:</span>
            <div>{{ $student->health->medications }}</div>
        </div>
        @endif
        
        @if($student->health->emergency_contact_name)
        <div>
            <span style="font-size: 0.85rem; color: #6B7280;">Contato de Emergência:</span>
            <div>{{ $student->health->emergency_contact_name }} - {{ $student->health->emergency_contact_phone }}</div>
        </div>
        @endif
    </div>
</div>
@endif
@endsection
