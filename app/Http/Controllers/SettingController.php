<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * Display settings page.
     */
    public function index()
    {
        $companySettings = Setting::where('group', 'company')->get();
        $financialSettings = Setting::where('group', 'financial')->get();
        $invoiceSettings = Setting::where('group', 'invoice')->get();
        
        return view('settings.index', compact(
            'companySettings',
            'financialSettings',
            'invoiceSettings'
        ));
    }
    
    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $settings = $request->input('settings', []);
        
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
        $request->validate([
            'logo' => 'required|image|max:2048',
        ]);
        
        $path = $request->file('logo')->store('settings', 'public');
        
        Setting::setValue('company_logo', $path);
        
        return back()->with('success', 'Logo atualizado com sucesso!');
    }
}
