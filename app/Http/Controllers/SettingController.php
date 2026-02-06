<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    private function ensureAdmin(): void
    {
        if (!auth()->user()?->canManageSettings()) {
            abort(403, 'Ação não autorizada.');
        }
    }

    /**
     * Display settings page.
     */
    public function index()
    {
        $this->ensureAdmin();

        $companySettings = Setting::where('group', 'company')->get();
        $financialSettings = Setting::where('group', 'financial')->get();
        $invoiceSettings = Setting::where('group', 'invoice')->get();
        $users = User::orderBy('name')->get();
        $roles = User::ROLES;
        
        return view('settings.index', compact(
            'companySettings',
            'financialSettings',
            'invoiceSettings',
            'users',
            'roles'
        ));
    }
    
    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $this->ensureAdmin();

        $settings = $request->input('settings', []);
        
        // Handle Logo Upload
        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('settings', 'public');
            Setting::setValue('company_logo', $path);
        }
        // Handle Signature Upload
        if ($request->hasFile('company_signature')) {
            $path = $request->file('company_signature')->store('settings', 'public');
            Setting::setValue('company_signature', $path);
        }

        foreach ($settings as $key => $value) {
            Setting::setValue($key, $value);
        }
        
        // Clear all setting caches
        Cache::flush();
        
        return back()->with('success', 'Configurações atualizadas com sucesso!');
    }
    
    /**
     * Upload company logo.
     */
    public function uploadLogo(Request $request)
    {
        $this->ensureAdmin();

        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,gif|max:500', // Limit to 500KB for PDF stability
        ]);
        
        $path = $request->file('logo')->store('settings', 'public');
        
        Setting::setValue('company_logo', $path);
        
        return back()->with('success', 'Logo atualizado com sucesso!');
    }

    /**
     * Create a new user.
     */
    public function storeUser(Request $request)
    {
        $this->ensureAdmin();

        $roles = array_keys(User::ROLES);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in($roles)],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Usuário criado com sucesso!');
    }

    /**
     * Update an existing user.
     */
    public function updateUser(Request $request, User $user)
    {
        $this->ensureAdmin();

        $roles = array_keys(User::ROLES);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in($roles)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Update current admin password.
     */
    public function updatePassword(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        if (!$user || !Hash::check($validated['current_password'], $user->password)) {
            return back()->with('error', 'Senha atual incorreta.');
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return back()->with('success', 'Senha atualizada com sucesso!');
    }
}