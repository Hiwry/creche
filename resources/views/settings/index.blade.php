@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Configurações</h1>
    </div>
</div>

<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="grid grid-2">
        <!-- Company Settings -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building" style="color: #7C3AED; margin-right: 10px;"></i>
                    Dados da Empresa
                </h3>
            </div>
            
            @foreach($companySettings as $setting)
            <div class="form-group">
                <label class="form-label">{{ $setting->label }}</label>
                
                @if($setting->key === 'company_logo')
                    @if($setting->value)
                    <div style="margin-bottom: 10px; padding: 10px; background: #f3f4f6; border-radius: 8px; display: inline-block;">
                        <img src="{{ asset('storage/' . $setting->value) }}" style="max-height: 80px; display: block;">
                    </div>
                    @endif
                    <input type="file" name="company_logo" class="form-control" accept="image/*">
                    <small style="color: #6B7280;">Deixe em branco para manter o atual</small>
                @else
                    <input type="text" name="settings[{{ $setting->key }}]" class="form-control" 
                           value="{{ $setting->value }}">
                @endif
            </div>
            @endforeach
        </div>
        
        <!-- Financial Settings -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-dollar-sign" style="color: #10B981; margin-right: 10px;"></i>
                    Configurações Financeiras
                </h3>
            </div>
            
            @foreach($financialSettings as $setting)
            @if(in_array($setting->key, ['extra_hour_rate', 'extra_hour_tolerance']))
            <div class="form-group">
                <label class="form-label">{{ $setting->label }}</label>
                <input type="{{ $setting->type === 'float' ? 'number' : 'text' }}" 
                       name="settings[{{ $setting->key }}]" 
                       class="form-control" 
                       value="{{ $setting->value }}"
                       @if($setting->type === 'float') step="0.01" @endif
                       @if($setting->type === 'integer') step="1" @endif>
                @if($setting->description)
                <small style="color: #6B7280;">{{ $setting->description }}</small>
                @endif
            </div>
            @endif
            @endforeach
        </div>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-file-invoice" style="color: #F59E0B; margin-right: 10px;"></i>
                Configurações de Fatura
            </h3>
        </div>
        
        <div class="grid grid-2">
            @foreach($invoiceSettings as $setting)
            <div class="form-group">
                <label class="form-label">{{ $setting->label }}</label>
                <input type="text" name="settings[{{ $setting->key }}]" class="form-control" 
                       value="{{ $setting->value }}">
            </div>
            @endforeach
        </div>
    </div>
    
    <div style="margin-top: 20px; text-align: right;">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Salvar Configurações
        </button>
    </div>
</form>
@endsection
