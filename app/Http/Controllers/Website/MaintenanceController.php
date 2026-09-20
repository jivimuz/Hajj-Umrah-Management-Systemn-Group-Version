<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\JsonResponse;

class MaintenanceController extends Controller
{
    public function run(): JsonResponse
    {
        try {
            $migrateExitCode = Artisan::call('migrate', [
                '--force' => true,
            ]);
            $migrateOutput = Artisan::output();

            $statusExitCode = Artisan::call('migrate:status');
            $statusOutput = Artisan::output();

            if ($migrateExitCode !== 0) {
                return response()->json([
                    'message' => 'Migration gagal. Optimize clear tidak dijalankan.',
                    'migration' => [
                        'exit_code' => $migrateExitCode,
                        'output' => $migrateOutput,
                    ],
                    'migration_status' => [
                        'exit_code' => $statusExitCode,
                        'output' => $statusOutput,
                    ],
                ], 500);
            }

            $clearExitCode = Artisan::call('optimize:clear');
            $clearOutput = Artisan::output();

            return response()->json([
                'message' => $clearExitCode === 0
                    ? 'Migration dan optimize clear berhasil dijalankan.'
                    : 'Migration berhasil, tetapi optimize clear gagal.',
                'migration' => [
                    'exit_code' => $migrateExitCode,
                    'output' => $migrateOutput,
                ],
                'migration_status' => [
                    'exit_code' => $statusExitCode,
                    'output' => $statusOutput,
                ],
                'optimize_clear' => [
                    'exit_code' => $clearExitCode,
                    'output' => $clearOutput,
                ],
            ], $clearExitCode === 0 ? 200 : 500);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Maintenance gagal dijalankan.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
