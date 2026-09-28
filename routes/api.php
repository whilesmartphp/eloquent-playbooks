<?php

use Illuminate\Support\Facades\Route;

$controller = config('playbooks.controller');
$write = (array) config('playbooks.write_middleware', []);
$groups = (array) config('playbooks.route_groups', []);

if ($groups['extraction'] ?? true) {
    Route::post('playbook-entries/extract', [$controller, 'extract'])->middleware($write)->name('playbooks.extract');
    Route::get('playbook-entries/extract/status', [$controller, 'extractStatus'])->name('playbooks.extract.status');
}

if ($groups['entries'] ?? true) {
    Route::get('playbook-entries', [$controller, 'index'])->name('playbooks.entries.index');
    Route::post('playbook-entries', [$controller, 'store'])->middleware($write)->name('playbooks.entries.store');
    Route::get('playbook-entries/{playbook_entry}', [$controller, 'show'])->name('playbooks.entries.show');
    Route::match(['put', 'patch'], 'playbook-entries/{playbook_entry}', [$controller, 'update'])->middleware($write)->name('playbooks.entries.update');
    Route::delete('playbook-entries/{playbook_entry}', [$controller, 'destroy'])->middleware($write)->name('playbooks.entries.destroy');
    Route::post('playbook-entries/{playbook_entry}/accept', [$controller, 'accept'])->middleware($write)->name('playbooks.entries.accept');
}
