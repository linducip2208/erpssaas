<?php

namespace Workdo\API\Http\Controllers;

use Workdo\API\Models\ApiLog;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ApiLogController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('view-api-logs')) {
            $logs = ApiLog::query()
                ->with(['token'])
                ->whereHas('token', function ($q) {
                    $q->where('created_by', creatorId());
                })
                ->when(request('method'), fn($q) => $q->where('method', request('method')))
                ->when(request('endpoint'), function ($q) {
                    $q->where('endpoint', 'like', '%' . request('endpoint') . '%');
                })
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest('created_at'))
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('API/API/Logs', [
                'logs' => $logs,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
