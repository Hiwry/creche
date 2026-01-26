@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Lista de Materiais Escolares</h1>
    </div>
</div>

<div class="grid grid-2">
    <!-- List of Materials -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Materiais Cadastrados</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Valor</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $material)
                        <tr>
                            <td>{{ $material->name }}</td>
                            <td>{{ $material->value ? 'R$ ' . number_format($material->value, 2, ',', '.') : '-' }}</td>
                            <td>{{ $material->description }}</td>
                            <td>
                                <form action="{{ route('school-materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja remover este material?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">Nenhum material cadastrado</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add New Material -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Adicionar Novo Material</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('school-materials.store') }}" method="POST">
                @csrf
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Nome do Material</label>
                    <input type="text" name="name" class="form-control" required placeholder="Ex: Caderno 96 folhas">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Valor (Opcional)</label>
                    <input type="number" step="0.01" min="0" name="value" class="form-control" placeholder="0,00">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Descrição (Opcional)</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Salvar Material</button>
            </form>
        </div>
    </div>
</div>
@endsection
