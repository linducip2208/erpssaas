<?php

namespace Workdo\FileSharing\Http\Controllers;

use Workdo\FileSharing\Models\SharedFolder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SharedFolderController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-file-sharing')) {
            $folders = SharedFolder::where('created_by', creatorId())
                ->whereNull('parent_id')
                ->with(['children', 'files', 'permissions.user'])
                ->latest()
                ->get();

            return Inertia::render('FileSharing/Index', [
                'folders' => $folders,
                'auth' => Auth::user(),
            ]);
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create-shared-folders')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'parent_id' => 'nullable|exists:shared_folders,id',
            ]);

            SharedFolder::create([
                'name' => $request->name,
                'parent_id' => $request->parent_id,
                'created_by' => creatorId(),
            ]);

            return redirect()->back()->with('success', __('Folder created successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->can('edit-shared-folders')) {
            $folder = SharedFolder::where('created_by', creatorId())->findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $folder->update(['name' => $request->name]);

            return redirect()->back()->with('success', __('Folder updated successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-shared-folders')) {
            $folder = SharedFolder::where('created_by', creatorId())->findOrFail($id);
            $folder->delete();

            return redirect()->back()->with('success', __('Folder deleted successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function show($id)
    {
        if (Auth::user()->can('view-shared-folders')) {
            $folder = SharedFolder::where('created_by', creatorId())
                ->with(['children', 'files.uploader', 'permissions.user'])
                ->findOrFail($id);

            return Inertia::render('FileSharing/Index', [
                'folder' => $folder,
                'folders' => SharedFolder::where('created_by', creatorId())->whereNull('parent_id')->with(['children', 'files'])->latest()->get(),
                'auth' => Auth::user(),
            ]);
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }
}
