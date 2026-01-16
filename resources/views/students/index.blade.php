@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Alunos</h1>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('students.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Aluno
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px;">
    <form action="{{ route('students.index') }}" method="GET" class="filter-form">
        <input type="text" name="search" class="form-control" 
               placeholder="Buscar por nome..." value="{{ request('search') }}" style="width: 250px;">
        
        <select name="status" class="form-control">
            <option value="">Todos os Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Ativo</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inativo</option>
            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspenso</option>
        </select>
        
        <select name="class_id" class="form-control">
            <option value="">Todas as Turmas</option>
            @foreach($classes as $class)
            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                {{ $class->name }}
            </option>
            @endforeach
        </select>
        
        <select name="payment_status" class="form-control">
            <option value="">Pagamento</option>
            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Em dia</option>
            <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Com pendência</option>
        </select>
        
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-filter"></i> Filtrar
        </button>
        
        @if(request()->hasAny(['search', 'status', 'class_id', 'payment_status']))
        <a href="{{ route('students.index') }}" class="btn btn-secondary">
            <i class="fas fa-times"></i> Limpar
        </a>
        @endif
    </form>
</div>

<!-- Students Table -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Responsável</th>
                    <th>Turma</th>
                    <th>Status</th>
                    <th>Mensalidade</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <img src="{{ $student->photo ? asset('storage/' . $student->photo) : 'https://ui-avatars.com/api/?name=' . urlencode($student->name) . '&background=7C3AED&color=fff' }}" 
                                 alt="" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <div>
                                <div style="font-weight: 500;">{{ $student->name }}</div>
                                @if($student->age)
                                <div style="font-size: 0.8rem; color: #6B7280;">{{ $student->age }} anos</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($student->guardian)
                        <div>{{ $student->guardian->name }}</div>
                        <div style="font-size: 0.8rem; color: #6B7280;">{{ $student->guardian->formatted_phone }}</div>
                        @else
                        <span style="color: #9CA3AF;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($student->activeEnrollments->count() > 0)
                            @foreach($student->activeEnrollments as $enrollment)
                            <span class="badge badge-info" style="margin-right: 4px;">
                                {{ $enrollment->classModel->name ?? '-' }}
                            </span>
                            @endforeach
                        @else
                        <span style="color: #9CA3AF;">Sem turma</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $student->status_color }}">
                            {{ $student->status_label }}
                        </span>
                    </td>
                    <td>
                        @if($student->pending_fees_count > 0)
                        <span class="badge badge-danger">
                            {{ $student->pending_fees_count }} pendente(s)
                        </span>
                        @else
                        <span class="badge badge-success">Em dia</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="{{ route('students.show', $student) }}" class="btn btn-secondary btn-sm" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('students.edit', $student) }}" class="btn btn-secondary btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <h3>Nenhum aluno encontrado</h3>
                            <p>Clique em "Novo Aluno" para cadastrar</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="pagination">
        {{ $students->withQueryString()->links() }}
    </div>
</div>
@endsection
