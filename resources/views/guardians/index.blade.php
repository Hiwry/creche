@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Responsáveis</h1>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('guardians.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Responsável
        </a>
    </div>
</div>

<!-- Search -->
<div class="card" style="margin-bottom: 20px;">
    <form action="{{ route('guardians.index') }}" method="GET" class="filter-form">
        <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CPF, telefone..." 
               value="{{ request('search') }}" style="width: 300px;">
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-search"></i> Buscar
        </button>
    </form>
</div>

<!-- Guardians Table -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Alunos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guardians as $guardian)
                <tr>
                    <td style="font-weight: 500;">{{ $guardian->name }}</td>
                    <td>{{ $guardian->formatted_cpf ?: '-' }}</td>
                    <td>{{ $guardian->formatted_phone ?: '-' }}</td>
                    <td>{{ $guardian->email ?: '-' }}</td>
                    <td>
                        <span class="badge badge-info">{{ $guardian->students_count }}</span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="{{ route('guardians.show', $guardian) }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('guardians.edit', $guardian) }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>Nenhum responsável cadastrado</h3>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="pagination">
        {{ $guardians->withQueryString()->links() }}
    </div>
</div>
@endsection
