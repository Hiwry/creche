@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('sports.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-left: 15px;">{{ $sport->name }}</h1>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('sports.edit', $sport) }}" class="btn btn-secondary">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>
</div>

<div class="grid grid-3">
    <!-- Sport Info -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informações</h3>
        </div>
        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Mensalidade</span>
                <span class="info-value">{{ $sport->formatted_monthly_fee }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value">
                    @if($sport->is_active)
                        <span class="badge badge-success">Ativo</span>
                    @else
                        <span class="badge badge-secondary">Inativo</span>
                    @endif
                </span>
            </div>
        </div>
        
        <div class="card-header" style="margin-top: 20px; border-top: 1px solid #E5E7EB; padding-top: 20px;">
            <h3 class="card-title">Horários</h3>
        </div>
        <div class="info-list">
            @forelse($sport->schedules as $schedule)
            <div class="info-item">
                <span class="info-label">{{ $schedule->day_of_week }}</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</span>
            </div>
            @empty
            <div style="padding: 10px; color: #6B7280; font-size: 0.9rem;">Nenhum horário definido.</div>
            @endforelse
        </div>
    </div>

    <!-- Enrollments -->
    <div class="col-span-2 card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">Alunos Matriculados</h3>
            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('enroll-modal').style.display='flex'">
                <i class="fas fa-user-plus"></i> Matricular Aluno
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Mensalidade</th>
                        <th>Data Início</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sport->students as $student)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <img src="{{ $student->photo_url }}" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 10px; object-fit: cover;">
                                <div>
                                    <div style="font-weight: 500;">{{ $student->name }}</div>
                                    <div style="font-size: 0.75rem; color: #6B7280;">{{ $student->guardian->name ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>R$ {{ number_format($student->pivot->monthly_fee, 2, ',', '.') }}</td>
                        <td>{{ \Carbon\Carbon::parse($student->pivot->start_date)->format('d/m/Y') }}</td>
                        <td style="text-align: right;">
                            <form action="{{ route('sports.unenroll', $student->pivot->id) }}" method="POST" onsubmit="return confirm('Encerrar matrícula deste aluno?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger" title="Encerrar Matrícula">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: #6B7280;">
                            Nenhum aluno matriculado neste esporte.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Enroll Modal -->
<div id="enroll-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div class="modal-content card" style="width: 100%; max-width: 500px; margin: 20px;">
        <div class="card-header" style="display: flex; justify-content: space-between;">
            <h3 class="card-title">Matricular Aluno em {{ $sport->name }}</h3>
            <button type="button" onclick="this.closest('.modal').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="{{ route('sports.enroll', $sport) }}" method="POST" style="padding: 20px;">
            @csrf
            <div class="form-group">
                <label class="form-label">Aluno</label>
                <select name="student_id" class="form-control" required>
                    <option value="">Selecione o aluno</option>
                    @foreach($availableStudents as $student)
                        <option value="{{ $student->id }}">{{ $student->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Valor Mensal (R$)</label>
                <input type="number" name="monthly_fee" class="form-control" step="0.01" value="{{ $sport->monthly_fee }}">
                <small style="color: #6B7280;">Deixe em branco para usar o valor padrão do esporte.</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Data de Início</label>
                <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            
            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary">Matricular</button>
            </div>
        </form>
    </div>
</div>

<style>
.col-span-2 { grid-column: span 2; }
.info-list { padding: 10px 0; }
.info-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #F3F4F6; }
.info-label { color: #6B7280; font-size: 0.9rem; }
.info-value { font-weight: 500; color: #111827; }
</style>
@endsection
