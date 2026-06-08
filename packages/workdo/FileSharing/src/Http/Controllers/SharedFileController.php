<?php

namespace Workdo\FileSharing\Http\Controllers;

use Workdo\FileSharing\Models\SharedFile;
use Workdo\FileSharing\Models\SharedFolder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SharedFileController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::user()->can('create-shared-files')) {
            $request->validate([
                'folder_id' => 'required|exists:shared_folders,id',
                'file' => 'required|file|max:102400',
            ]);

            $folder = SharedFolder::where('created_by', creatorId())->findOrFail($request->folder_id);
            $file = $request->file('file');
            $path = $file->store('shared-files/' . creatorId(), 'public');

            SharedFile::create([
                'folder_id' => $folder->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => Auth::id(),
            ]);

            return redirect()->back()->with('success', __('File uploaded successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function destroy($id)
    {
        $file = SharedFile::whereHas('folder', function ($q) {
            $q->where('created_by', creatorId());
        })->findOrFail($id);

        if (Auth::user()->can('delete-shared-files')) {
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
            $file->delete();

            return redirect()->back()->with('success', __('File deleted successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function download($id)
    {
        $file = SharedFile::whereHas('folder', function ($q) {
            $q->where('created_by', creatorId());
        })->findOrFail($id);

        if (Auth::user()->can('download-shared-files')) {
            if (!Storage::disk('public')->exists($file->file_path)) {
                return redirect()->back()->with('error', __('File not found.'));
            }

            return Storage::disk('public')->download($file->file_path, $file->file_name);
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function downloadViaToken($token)
    {
        $link = \Workdo\FileSharing\Models\SharedLink::where('token', $token)->first();

        if (!$link || !$link->isValid()) {
            return redirect()->back()->with('error', __('Link is invalid or expired.'));
        }

        $file = $link->file;

        if (!Storage::disk('public')->exists($file->file_path)) {
            return redirect()->back()->with('error', __('File not found.'));
        }

        return Storage::disk('public')->download($file->file_path, $file->file_name);
    }
}
