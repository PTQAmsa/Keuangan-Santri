<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController
{
    /**
     * Log Aktivitas Sistem (khusus Administrator)
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $userId = $request->input('user_id');
        $module = ActivityModule::tryFrom((string) $request->input('module'));
        $action = ActivityAction::tryFrom((string) $request->input('action'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = ActivityLog::with(['user', 'student'])->orderByDesc('id');

        if ($search) {
            $query->where('description', 'like', "%{$search}%");
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($module) {
            $query->where('module', $module->value);
        }

        if ($action) {
            $query->where('action', $action->value);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $logs = $query->paginate(20)->withQueryString();

        $users = User::orderBy('name')->get();
        $totalLogs = ActivityLog::count();
        $totalToday = ActivityLog::whereDate('created_at', now()->toDateString())->count();
        $totalFinancial = ActivityLog::whereIn('module', [
            ActivityModule::Invoice->value,
            ActivityModule::Payment->value,
            ActivityModule::Expense->value,
        ])->count();

        return view('admin.activity-logs', compact(
            'logs',
            'users',
            'totalLogs',
            'totalToday',
            'totalFinancial',
            'search',
            'userId',
            'module',
            'action',
            'startDate',
            'endDate'
        ));
    }
}
