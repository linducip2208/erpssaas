<?php

namespace Workdo\FileSharing\Http\Controllers;

use Workdo\FileSharing\Models\FolderPermission;
use Workdo\FileSharing\Models\SharedFolder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class FolderPermissionController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::user()->can('create-folder-permissions')) {
            $request->validate([
                'folder_id' => 'required|exists:shared_folders,id',
                'user_id' => 'required|exists:users,id',
                'permission' => 'required|in:read,write,admin',
            ]);

            $folder = SharedFolder::where('created_by', creatorId())->findOrFail($request->folder_id);

            FolderPermission::updateOrCreate(
                ['folder_id' => $folder->id, 'user_id' => $request->user_id],
                ['permission' => $request->permission]
            );

            return redirect()->back()->with('success', __('Permission added successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-folder-permissions')) {
            $permission = FolderPermission::whereHas('folder', function ($q) {
                $q->where('created_by', creatorId());
            })->findOrFail($id);

            $permission->delete();

            return redirect()->back()->with('success', __('Permission removed successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function users($folderId)
    {
        $folder = SharedFolder::where('created_by', creatorId())->findOrFail($folderId);

        $users = User::where('created_by', creatorId())
            ->select('id', 'name', 'email')
            ->get();

        return response()->json([
            'users' => $users,
            'permissions' => $folder->permissions()->with('user')->get(),
        ]);
    }
}
