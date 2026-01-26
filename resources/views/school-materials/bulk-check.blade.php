@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Checklist de Materiais Escolares</h1>
    </div>
    <div class="action-bar-right">
        <form action="{{ route('school-materials.bulk-check') }}" method="GET" class="filter-form">
            <select name="class_id" class="form-control" onchange="this.form.submit()">
                <option value="">Todas as Turmas</option>
                @foreach($classes as $class)
                <option value="{{ $class->id }}" {{ $classId == $class->id ? 'selected' : '' }}>
                    {{ $class->name }}
                </option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div class="card">
    <form action="{{ route('school-materials.update-bulk-check') }}" method="POST">
        @csrf
        <div class="table-container">
            <table class="table-sticky">
                <thead>
                    <tr>
                        <th style="min-width: 200px; position: sticky; left: 0; background: #fff; z-index: 2;">Aluno</th>
                        @foreach($materials as $material)
                        <th class="text-center" title="{{ $material->description }}">{{ $material->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    <tr>
                        <td style="position: sticky; left: 0; background: #fff; z-index: 1;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <img src="{{ $student->photo ? asset('storage/' . $student->photo) : 'https://ui-avatars.com/api/?name=' . urlencode($student->name) }}" 
                                     style="width: 30px; height: 30px; border-radius: 50%;">
                                {{ $student->name }}
                            </div>
                        </td>
                        @foreach($materials as $material)
                        @php
                            $studentMaterial = $student->studentMaterials->where('material_id', $material->id)->first();
                            $checked = $studentMaterial && $studentMaterial->received;
                        @endphp
                        <td class="text-center">
                            <input type="hidden" name="materials[{{ $student->id }}][{{ $material->id }}]" value="0">
                            <input type="checkbox" name="materials[{{ $student->id }}][{{ $material->id }}]" value="1" {{ $checked ? 'checked' : '' }}>
                        </td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($materials) + 1 }}" class="text-center">Nenhum aluno encontrado</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($students->count() > 0)
        <div class="card-footer" style="padding: 20px; text-align: right; border-top: 1px solid #E5E7EB;">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
        @endif
    </form>
</div>

<style>
    .table-sticky {
        border-collapse: separate;
        border-spacing: 0;
    }
    .text-center { text-align: center; }
</style>
@endsection
