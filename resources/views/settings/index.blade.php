@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Configurações</h1>
    </div>
</div>

@if($errors->any())
<div class="card" style="margin-bottom: 20px;">
    <div class="alert alert-danger" style="margin: 16px;">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

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
                
                @if(in_array($setting->key, ['company_logo', 'company_signature']))
                    @php
                        $isSignature = $setting->key === 'company_signature';
                        $sizeHint = $isSignature ? 'Tamanho ideal: 600x180px' : 'Tamanho ideal: 300x300px';
                        $previewData = null;
                        if ($setting->value) {
                            $previewPath = storage_path('app/public/' . $setting->value);
                            if (file_exists($previewPath)) {
                                $type = strtolower(pathinfo($previewPath, PATHINFO_EXTENSION));
                                if (in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                                    $previewData = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($previewPath));
                                }
                            }
                        }
                    @endphp
                    @if($previewData)
                    <div style="margin-bottom: 10px; padding: 10px; background: #f3f4f6; border-radius: 8px; display: inline-block;">
                        <img src="{{ $previewData }}" style="max-height: 80px; display: block;">
                    </div>
                    @elseif($setting->value)
                    <div style="margin-bottom: 10px;">
                        <small style="color: #DC2626;">Arquivo atual não encontrado no storage.</small>
                    </div>
                    @endif
                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <input type="file" name="{{ $setting->key }}" class="form-control" accept="{{ $isSignature ? 'image/png' : 'image/*' }}" style="flex: 1 1 260px;">
                        <small style="color: #6B7280; white-space: nowrap;">{{ $sizeHint }}</small>
                    </div>
                    <small style="color: #6B7280;">
                        {{ $isSignature ? 'Preferencialmente PNG com fundo transparente.' : 'Deixe em branco para manter o atual.' }}
                    </small>
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

<div class="grid grid-2" style="margin-top: 20px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-plus" style="color: #2563EB; margin-right: 10px;"></i>
                Criar Usuário
            </h3>
        </div>
        <form action="{{ route('settings.users.store') }}" method="POST">
            @csrf
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail *</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Perfil *</label>
                    <select name="role" class="form-control" required>
                        @foreach($roles as $roleKey => $roleLabel)
                        <option value="{{ $roleKey }}" {{ old('role') === $roleKey ? 'selected' : '' }}>{{ $roleLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Senha *</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar Senha *</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
            <div style="margin-top: 10px; text-align: right;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Criar Usuário
                </button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-key" style="color: #10B981; margin-right: 10px;"></i>
                Alterar Minha Senha
            </h3>
        </div>
        <form action="{{ route('settings.password') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Senha Atual *</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nova Senha *</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Confirmar Nova Senha *</label>
                <input type="password" name="new_password_confirmation" class="form-control" required>
            </div>
            <div style="margin-top: 10px; text-align: right;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Atualizar Senha
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users" style="color: #7C3AED; margin-right: 10px;"></i>
            Usuários
        </h3>
    </div>

    @forelse($users as $user)
    <form action="{{ route('settings.users.update', $user) }}" method="POST" style="padding: 16px; border-top: 1px solid #E5E7EB;">
        @csrf
        @method('PUT')
        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">Nome</label>
                <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Perfil</label>
                <select name="role" class="form-control" required>
                    @foreach($roles as $roleKey => $roleLabel)
                    <option value="{{ $roleKey }}" {{ $user->role === $roleKey ? 'selected' : '' }}>{{ $roleLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Nova Senha</label>
                <input type="password" name="password" class="form-control" placeholder="Deixe em branco para manter">
            </div>
            <div class="form-group">
                <label class="form-label">Confirmar Nova Senha</label>
                <input type="password" name="password_confirmation" class="form-control" placeholder="Deixe em branco para manter">
            </div>
        </div>
        <div style="text-align: right;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </form>
    @empty
    <div style="padding: 16px; color: #6B7280;">Nenhum usuário encontrado.</div>
    @endforelse
</div>
@endsection
