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
                                <div style="display: flex; gap: 5px;">
                                    <button type="button" class="btn btn-success btn-sm" 
                                            onclick="openUsageModal('{{ $material->id }}', '{{ $material->name }}')"
                                            title="Registrar Uso">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <form action="{{ route('school-materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja remover este material?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Remover">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
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

<!-- Usage Modal -->
<div id="usage-modal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div style="background:#fff; margin: auto; padding:20px; width:90%; max-width:450px; border-radius:12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.25rem;">Registrar Uso de Material</h3>
            <button type="button" onclick="closeUsageModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6B7280;">&times;</button>
        </div>
        
        <form action="{{ route('school-materials.record-usage') }}" method="POST">
            @csrf
            <input type="hidden" name="school_material_id" id="modal-material-id">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Material</label>
                <input type="text" id="modal-material-name" class="form-control" readonly style="background-color: #F3F4F6;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Aluno *</label>
                <select name="student_id" class="form-control" required id="usage-student-select">
                    <option value="">-- Selecione o Aluno --</option>
                    @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-2">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label">Data *</label>
                    <input type="date" name="usage_date" class="form-control" required id="modal-usage-date">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label">Quantidade *</label>
                    <input type="number" name="quantity" class="form-control" required min="1" value="1">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Observações</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Opcional..."></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeUsageModal()">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Registrar e Cobrar
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openUsageModal(id, name) {
    document.getElementById('modal-material-id').value = id;
    document.getElementById('modal-material-name').value = name;
    
    // Set default date to today
    const now = new Date();
    const today = now.toISOString().split('T')[0];
    document.getElementById('modal-usage-date').value = today;
    
    document.getElementById('usage-modal').style.display = 'flex';
}

function closeUsageModal() {
    document.getElementById('usage-modal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('usage-modal');
    if (event.target == modal) {
        closeUsageModal();
    }
}
</script>
@endpush
@endsection
