<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateBackupRequest;
use App\Services\BackupDiagnosticsService;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class BackupController extends Controller
{
    public function __construct(
        private BackupService $backupService,
        private BackupDiagnosticsService $diagnostics,
    ) {}

    /**
     * GET /api/admin/backups/diagnostics
     */
    public function diagnostics(): JsonResponse
    {
        $report  = $this->diagnostics->run();
        $hasFail = collect($report)->contains('status', 'fail');

        return response()->json([
            'healthy' => !$hasFail,
            'report'  => $report,
        ], $hasFail ? 207 : 200);
    }

    /**
     * GET /api/admin/backups
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->backupService->listBackups(),
        ]);
    }

    /**
     * POST /api/admin/backups
     */
    public function store(CreateBackupRequest $request): JsonResponse
    {
        try {
            $backup = $this->backupService->createBackup($request->validated());

            return response()->json([
                'message' => 'تم إنشاء النسخة الاحتياطية بنجاح.',
                'data'    => $backup,
            ], 201);

        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'فشل إنشاء النسخة الاحتياطية.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/admin/backups/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $backup = $this->backupService->restoreBackup($id);

            return response()->json([
                'message' => 'تمت استعادة قاعدة البيانات بنجاح.',
                'data'    => [
                    'id'        => $backup->id,
                    'file_name' => $backup->file_name,
                    'type'      => $backup->type,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'message' => 'النسخة الاحتياطية غير موجودة.',
            ], 404);

        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'فشلت عملية الاستعادة.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/backups/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->backupService->deleteBackup($id);

            return response()->json([
                'message' => 'تم حذف النسخة الاحتياطية بنجاح.',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'message' => 'النسخة الاحتياطية غير موجودة.',
            ], 404);

        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'فشل حذف النسخة الاحتياطية.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/admin/backups/{id}/download
     */
    public function download(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        try {
            $backup = $this->backupService->getBackup($id);
            $path = $this->backupService->getBackupPath($backup);

            return response()->download($path, $backup->file_name);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            abort(404, 'النسخة الاحتياطية غير موجودة.');

        } catch (RuntimeException $e) {
            abort(500, 'فشل تحميل النسخة الاحتياطية: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/admin/backups/upload
     */
    public function upload(\App\Http\Requests\Admin\UploadBackupRequest $request): JsonResponse
    {
        try {
            $backup = $this->backupService->uploadBackup($request->file('file'));

            return response()->json([
                'message' => 'تم رفع النسخة الاحتياطية بنجاح.',
                'data'    => [
                    'id'             => $backup->id,
                    'file_name'      => $backup->file_name,
                    'type'           => $backup->type,
                    'size'           => $backup->size,
                    'size_formatted' => $backup->formattedSize(),
                    'created_at'     => $backup->created_at->toIso8601String(),
                ],
            ], 201);

        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'فشل رفع النسخة الاحتياطية.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/admin/backups/history
     */
    public function history(): JsonResponse
    {
        return response()->json([
            'data' => $this->backupService->getHistory(50),
        ]);
    }
}
