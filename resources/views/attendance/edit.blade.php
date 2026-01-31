@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Editar Registro de Frequência</h1>
        <span style="color: #6B7280; margin-left: 10px;">
            {{ \Carbon\Carbon::parse($log->date)->format('d/m/Y') }} - {{ $log->student->name }}
        </span>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('attendance.index', ['date' => $log->date]) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Detalhes da Frequência</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('attendance.update', $log->id) }}" method="POST"
              data-extra-calculator
              data-hourly-rate="{{ number_format($hourlyRate ?? 0, 2, '.', '') }}"
              data-tolerance="{{ $tolerance ?? 0 }}"
              data-expected-start="{{ $log->expected_start ? \Carbon\Carbon::parse($log->expected_start)->format('H:i') : '' }}"
              data-expected-end="{{ $log->expected_end ? \Carbon\Carbon::parse($log->expected_end)->format('H:i') : '' }}">
            @csrf
            @method('PUT')
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Aluno</label>
                <div style="font-size: 1.1rem; font-weight: 500;">{{ $log->student->name }}</div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Horário Entrada</label>
                    <input type="time" id="check_in" name="check_in" class="form-control" value="{{ $log->check_in ? \Carbon\Carbon::parse($log->check_in)->format('H:i') : '' }}">
                </div>
                
                <div class="form-group">
                    <label>Horário Saída</label>
                    <input type="time" id="check_out" name="check_out" class="form-control" value="{{ $log->check_out ? \Carbon\Carbon::parse($log->check_out)->format('H:i') : '' }}">
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label>Retirado por</label>
                <input type="text" name="picked_up_by" class="form-control" value="{{ $log->picked_up_by }}" placeholder="Nome do responsável">
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label>Observações</label>
                <textarea name="notes" class="form-control" rows="3">{{ $log->notes }}</textarea>
            </div>
            
            <div class="info-box" style="margin-top: 20px; padding: 15px; background-color: #F3F4F6; border-radius: 8px;">
                <h4 style="font-size: 0.9rem; font-weight: 600; margin-bottom: 10px;">Cálculo de Extras (Personalizado)</h4>
                <input type="hidden" name="extra_manual" id="extra_manual" value="{{ ($isManualExtra ?? false) ? 1 : 0 }}">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Minutos Extras</label>
                        <input type="number" id="extra_minutes" name="extra_minutes" class="form-control" value="{{ $log->extra_minutes }}">
                    </div>
                    <div class="form-group">
                        <label>Valor Extra (R$)</label>
                        <input type="number" id="extra_charge" name="extra_charge" class="form-control" value="{{ number_format($log->extra_charge ?? 0, 2, '.', '') }}" readonly>
                    </div>
                </div>
                <div style="margin-top: 10px; display: flex; justify-content: space-between; gap: 10px; align-items: center;">
                    <div style="font-size: 0.8rem; color: #6B7280;">
                        Valor/hora: R$ {{ number_format($hourlyRate ?? 0, 2, ',', '.') }} · Tolerância: {{ $tolerance ?? 0 }} min
                    </div>
                    <button type="button" id="extra_recalculate" class="btn btn-secondary btn-sm">Recalcular</button>
                </div>
                <div style="margin-top: 10px; font-size: 0.8rem; color: #6B7280;">
                    Horário Previsto: {{ \Carbon\Carbon::parse($log->expected_start)->format('H:i') }} - {{ \Carbon\Carbon::parse($log->expected_end)->format('H:i') }}
                </div>
            </div>
            
            <div class="form-actions" style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-extra-calculator]');
    if (!form) return;

    const hourlyRate = parseFloat(form.dataset.hourlyRate || '0');
    const tolerance = parseInt(form.dataset.tolerance || '0', 10);
    const expectedStart = form.dataset.expectedStart || '';
    const expectedEnd = form.dataset.expectedEnd || '';

    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const minutesInput = document.getElementById('extra_minutes');
    const chargeInput = document.getElementById('extra_charge');
    const manualInput = document.getElementById('extra_manual');
    const recalcButton = document.getElementById('extra_recalculate');

    const timeToMinutes = (value) => {
        if (!value) return null;
        const [hours, minutes] = value.split(':').map(Number);
        if (Number.isNaN(hours) || Number.isNaN(minutes)) return null;
        return (hours * 60) + minutes;
    };

    const calculateExtraMinutes = () => {
        const checkIn = timeToMinutes(checkInInput?.value);
        const checkOut = timeToMinutes(checkOutInput?.value);
        const expectedStartMinutes = timeToMinutes(expectedStart);
        const expectedEndMinutes = timeToMinutes(expectedEnd);

        if (expectedStartMinutes === null || expectedEndMinutes === null) {
            return 0;
        }

        let extraMinutes = 0;

        if (checkIn !== null && checkIn < expectedStartMinutes) {
            const earlyMinutes = expectedStartMinutes - checkIn;
            if (earlyMinutes > tolerance) {
                extraMinutes += (earlyMinutes - tolerance);
            }
        }

        if (checkOut !== null && checkOut > expectedEndMinutes) {
            let startPoint = expectedEndMinutes;
            if (checkIn !== null && checkIn > expectedEndMinutes) {
                startPoint = checkIn;
            }
            const lateMinutes = checkOut - startPoint;
            if (lateMinutes > tolerance) {
                extraMinutes += (lateMinutes - tolerance);
            }
        }

        return extraMinutes;
    };

    const updateCharge = () => {
        const minutes = parseInt(minutesInput?.value || '0', 10) || 0;
        const charge = (minutes / 60) * hourlyRate;
        if (chargeInput) {
            chargeInput.value = charge.toFixed(2);
        }
    };

    const applyAutoCalculation = () => {
        const minutes = calculateExtraMinutes();
        if (minutesInput) {
            minutesInput.value = minutes;
        }
        if (manualInput) {
            manualInput.value = '0';
        }
        updateCharge();
    };

    if (manualInput?.value === '1') {
        updateCharge();
    } else {
        applyAutoCalculation();
    }

    if (minutesInput) {
        minutesInput.addEventListener('input', () => {
            if (manualInput) {
                manualInput.value = '1';
            }
            updateCharge();
        });
    }

    const onTimeChange = () => {
        if (manualInput?.value === '1') return;
        applyAutoCalculation();
    };

    checkInInput?.addEventListener('change', onTimeChange);
    checkOutInput?.addEventListener('change', onTimeChange);

    recalcButton?.addEventListener('click', () => {
        applyAutoCalculation();
    });
})();
</script>
@endpush
