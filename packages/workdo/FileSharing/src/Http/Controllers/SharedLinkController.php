<?php

namespace Workdo\FileSharing\Http\Controllers;

use Workdo\FileSharing\Models\SharedLink;
use Workdo\FileSharing\Models\SharedFile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SharedLinkController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::user()->can('create-shared-links')) {
            $request->validate([
                'file_id' => 'required|exists:shared_files,id',
                'expires_at' => 'nullable|date|after:now',
            ]);

            $file = SharedFile::whereHas('folder', function ($q) {
                $q->where('created_by', creatorId());
            })->findOrFail($request->file_id);

            $link = SharedLink::create([
                'file_id' => $file->id,
                'token' => Str::random(32),
                'expires_at' => $request->expires_at,
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);

            return redirect()->back()->with('success', __('Share link created: ') . route('shared-files.download-link', $link->token));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-shared-links')) {
            $link = SharedLink::whereHas('file.folder', function ($q) {
                $q->where('created_by', creatorId());
            })->findOrFail($id);

            $link->delete();

            return redirect()->back()->with('success', __('Share link deleted successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function toggle($id)
    {
        $link = SharedLink::whereHas('file.folder', function ($q) {
            $q->where('created_by', creatorId());
        })->findOrFail($id);

        $link->update(['is_active' => !$link->is_active]);

        return redirect()->back()->with('success', __('Share link status updated.'));
    }
}
