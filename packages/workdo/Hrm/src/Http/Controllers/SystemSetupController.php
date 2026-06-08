<?php

namespace Workdo\Hrm\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SystemSetupController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-settings')) {
            return Inertia::render('Hrm/SystemSetup/Index');
        }
        return back()->with('error', __('Permission denied'));
    }
}
