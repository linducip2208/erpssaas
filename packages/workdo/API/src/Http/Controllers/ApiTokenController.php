<?php

namespace Workdo\API\Http\Controllers;

use Workdo\API\Models\ApiToken;
use Workdo\API\Http\Requests\StoreApiTokenRequest;
use Workdo\API\Http\Requests\UpdateApiTokenRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ApiTokenController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-api-tokens')) {
            $tokens = ApiToken::query()
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-api-tokens')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-api-tokens')) {
                        $q->where('created_by', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), function ($q) {
                    $q->where('name', 'like', '%' . request('name') . '%');
                })
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('API/API/Index', [
                'tokens' => $tokens,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreApiTokenRequest $request)
    {
        if (Auth::user()->can('create-api-tokens')) {
            $validated = $request->validated();

            $token = new ApiToken();
            $token->name = $validated['name'];
            $token->token = Str::random(64);
            $token->permissions = $validated['permissions'] ?? [];
            $token->expires_at = $validated['expires_at'] ?? null;
            $token->is_active = true;
            $token->created_by = creatorId();
            $token->save();

            return redirect()->route('api.tokens.index')->with('success', __('The API token has been created successfully.'));
        } else {
            return redirect()->route('api.tokens.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateApiTokenRequest $request, ApiToken $token)
    {
        if (Auth::user()->can('edit-api-tokens')) {
            $validated = $request->validated();

            $token->name = $validated['name'];
            $token->permissions = $validated['permissions'] ?? $token->permissions;
            $token->expires_at = $validated['expires_at'] ?? $token->expires_at;
            $token->is_active = $validated['is_active'] ?? $token->is_active;
            $token->save();

            return back()->with('success', __('The API token details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function regenerate(ApiToken $token)
    {
        if (Auth::user()->can('regenerate-api-tokens')) {
            $token->token = Str::random(64);
            $token->save();

            return back()->with('success', __('The API token has been regenerated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function revoke(ApiToken $token)
    {
        if (Auth::user()->can('revoke-api-tokens')) {
            $token->is_active = false;
            $token->save();

            return back()->with('success', __('The API token has been revoked successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(ApiToken $token)
    {
        if (Auth::user()->can('delete-api-tokens')) {
            $token->delete();

            return redirect()->back()->with('success', __('The API token has been deleted.'));
        } else {
            return redirect()->route('api.tokens.index')->with('error', __('Permission denied'));
        }
    }
}
