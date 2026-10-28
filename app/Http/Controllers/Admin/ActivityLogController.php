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

        // Kolom yang bisa diurutkan lewat klik judul kolom.
        $allowedSorts = [
            'time' => 'activity_logs.created_at',
            'user' => 'users.name',
            'module' => 'activity_logs.module',
            'action' => 'activity_logs.action',
            'description' => 'activity_logs.description',
        ];

        $sortBy = (string) $request->input('sort_by', 'time');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! array_key_exists($sortBy, $allowedSorts)) {
            $sortBy = 'time';
        }

        $query = ActivityLog::with(['user', 'student'])->select('activity_logs.*');

        if ($sortBy === 'user') {
            $query->leftJoin('users', 'activity_logs.user_id', '=', 'users.id');
        }

        $query->orderBy($allowedSorts[$sortBy], $sortOrder)->orderByDesc('activity_logs.id');

        if ($search) {
            $query->whereLike('description', "%{$search}%");
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
            $query->whereDate('activity_logs.created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('activity_logs.created_at', '<=', $endDate);
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
            'endDate',
            'sortBy',
            'sortOrder'
        ));
    }
}
