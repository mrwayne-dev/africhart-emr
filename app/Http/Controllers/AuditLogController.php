<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\AuditLogRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends BaseController
{
    public function __construct(
        protected AuditLogRepository $auditLogRepository
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('view-audit-log');

        $logs = $this->auditLogRepository->getPaginated(
            search: $request->input('search'),
            modelType: $request->input('model_type'),
            userId: $request->input('user_id'),
        );

        return view('audit.index', [
            'logs' => $logs,
            'modelTypes' => $this->auditLogRepository->distinctModelTypes(),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
