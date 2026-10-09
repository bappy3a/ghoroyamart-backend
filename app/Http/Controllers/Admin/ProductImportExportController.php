<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductSpreadsheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductImportExportController extends Controller
{
    public function index(): View
    {
        return view('admin.products.import-export');
    }

    public function template(ProductSpreadsheet $spreadsheet): StreamedResponse
    {
        return $this->download($spreadsheet->template(), 'product-import-template.xlsx');
    }

    public function export(ProductSpreadsheet $spreadsheet): StreamedResponse
    {
        return $this->download($spreadsheet->export(), 'products-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function import(Request $request, ProductSpreadsheet $spreadsheet): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ], [
            'file.mimes' => 'Please upload an .xlsx Excel file (use the template).',
        ]);

        try {
            $result = $spreadsheet->import($request->file('file')->getRealPath(), $request->user()?->id);
        } catch (Throwable $e) {
            report($e);

            return back()->with('import_errors', [0 => ['The file could not be read. Please use the downloaded template.']]);
        }

        if ($result['errors']) {
            return back()->with('import_errors', $result['errors']);
        }

        Cache::forget('api.home');
        flash_message("Import complete: {$result['created']} product(s) created, {$result['updated']} updated, {$result['variants']} variant(s) saved.");

        return redirect()->route('products.index');
    }

    protected function download($spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
