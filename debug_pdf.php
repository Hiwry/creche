<?php

use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Get the latest invoice
    $invoice = Invoice::latest()->firstOrFail();
    echo "Processing Invoice ID: " . $invoice->id . "\n";
    
    $invoice->load(['student.guardian', 'items']);
    
    $settings = array_merge(
        Setting::getByGroup('company'),
        Setting::getByGroup('financial'),
        Setting::getByGroup('invoice')
    );
    
    // Test view rendering first (often where the error is)
    $view = view('invoices.pdf', [
        'invoice' => $invoice,
        'settings' => $settings,
    ])->render();
    
    echo "View rendered successfully.\n";
    
    // Test PDF generation
    $pdf = Pdf::loadHTML($view);
    $output = $pdf->output();
    
    echo "PDF generated successfully. Size: " . strlen($output) . " bytes\n";
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
