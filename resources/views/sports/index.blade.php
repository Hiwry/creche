@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Esportes</h1>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('sports.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Esporte
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Mensalidade</th>
                    <th>Horários</th>
                    <th>Alunos</th>
                    <th>Status</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sports as $sport)
                <tr>
                    <td>
                        <div style="font-weight: 500; color: #111827;">{{ $sport->name }}</div>
                    </td>
                    <td>{{ $sport->formatted_monthly_fee }}</td>
                    <td>
                        @foreach($sport->schedules as $schedule)
                            <div style="font-size: 0.85rem; color: #6B7280;">
                                {{ $schedule->day_of_week }}: {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                            </div>
                        @endforeach
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $sport->students_count }}</span>
                    </td>
                    <td>
                        @if($sport->is_active)
                            <span class="badge badge-success">Ativo</span>
                        @else
                            <span class="badge badge-secondary">Inativo</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('sports.show', $sport) }}" class="btn btn-sm btn-secondary" title="Ver Detalhes">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('sports.edit', $sport) }}" class="btn btn-sm btn-secondary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('sports.destroy', $sport) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Tem certeza que deseja remover este esporte?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #6B7280;">
                        Nenhum esporte cadastrado.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
