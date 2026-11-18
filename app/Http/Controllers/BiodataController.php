<?php

namespace App\Http\Controllers;

use App\Models\BioModel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Profile settings (users/settings), ported from csskabba-portal without the
 * staff / student / parent sections. A person can always edit their own
 * profile; editing someone else's needs the "Update user" permission.
 */
class BiodataController extends Controller
{
    private const PROFILE_RULES = [
        'fname'         => 'required|string|max:100',
        'lname'         => 'required|string|max:100',
        'oname'         => 'nullable|string|max:100',
        'phone'         => 'nullable|string|max:20',
        'gender'        => 'nullable|in:male,female,other',
        'maritalstatus' => 'nullable|string|max:20',
        'nationality'   => 'nullable|string|max:100',
        'dob'           => 'nullable|date',
        'address'       => 'nullable|string|max:500',
    ];

    public function index(): RedirectResponse
    {
        return redirect()->route('user.settings', auth()->id());
    }

    public function show(string $id): View
    {
        $user = User::with(['bio', 'roles'])->findOrFail($id);
        $this->authorizeEdit($user, false);

        $userbio   = $user->bio;
        $canEdit   = $this->canEdit($user);
        $pagetitle = $user->name . ' - Profile Settings';

        return view('users.settings', compact('user', 'userbio', 'canEdit', 'pagetitle'));
    }

    /** Kept for the old biodata.store route: same as updateProfile. */
    public function store(Request $request): RedirectResponse
    {
        return $this->updateProfile($request);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $request->validate(['id' => 'required|exists:users,id'] + self::PROFILE_RULES);
        $user = User::findOrFail($request->id);
        $this->authorizeEdit($user);

        try {
            $user->forceFill([
                'first_name'    => $request->fname,
                'last_name'     => $request->lname,
                'phone_number'  => $request->phone ?: $user->phone_number,
                'gender'        => $request->gender ?: $user->gender,
                'date_of_birth' => $request->dob ?: $user->date_of_birth,
            ])->save();

            BioModel::updateOrCreate(['user_id' => $user->id], [
                'firstname'     => $request->fname,
                'lastname'      => $request->lname,
                'othernames'    => $request->oname ?? '',
                'phone'         => $request->phone ?? '',
                'gender'        => $request->gender ?? '',
                'maritalstatus' => $request->maritalstatus ?? '',
                'nationality'   => $request->nationality ?? '',
                'dob'           => $request->dob,
                'address'       => $request->address ?? '',
            ]);

            return back()->with('success', 'Personal information updated successfully!');
        } catch (\Throwable $e) {
            Log::error('Profile update error: ' . $e->getMessage());

            return back()->with('error', 'Failed to update profile.');
        }
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:users,id',
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user = User::findOrFail($request->id);
        if (! $this->canEdit($user)) {
            return response()->json(['success' => false, 'message' => 'You cannot change this photo.'], 403);
        }

        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }
        $path = $request->file('avatar')->store('profile_images', 'public');
        $user->forceFill(['profile_image' => $path])->save();

        return response()->json([
            'success'    => true,
            'message'    => 'Profile photo updated!',
            'avatar_url' => asset('storage/' . $path),
        ]);
    }

    public function ajaxemailupdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'userid'                    => 'required|exists:users,id',
            'emailaddress'              => 'required|email|unique:users,email,' . $request->userid,
            'emailaddress_confirmation' => 'required|same:emailaddress',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user = User::findOrFail($request->userid);
        if (! $this->canEdit($user)) {
            return response()->json(['success' => false, 'message' => 'You cannot change this email.'], 403);
        }
        $user->forceFill(['email' => $request->emailaddress, 'username' => $request->emailaddress])->save();

        return response()->json(['success' => true, 'message' => 'Email updated successfully!']);
    }

    public function ajaxpasswordupdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'userid'   => 'required|exists:users,id',
            'password' => 'required|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user = User::findOrFail($request->userid);
        if (! $this->canEdit($user)) {
            return response()->json(['success' => false, 'message' => 'You cannot change this password.'], 403);
        }
        // Only the hash is kept; the old plain-text copy (wpassword) is cleared.
        $user->forceFill(['password' => Hash::make($request->password), 'wpassword' => null])->save();

        return response()->json(['success' => true, 'message' => 'Password updated successfully!']);
    }

    public function create(): RedirectResponse { return $this->index(); }
    public function edit(string $id): RedirectResponse { return redirect()->route('user.settings', $id); }
    public function update(Request $request, string $id): RedirectResponse { return $this->updateProfile($request->merge(['id' => $id])); }
    public function destroy(string $id): RedirectResponse { return $this->index(); }

    private function canEdit(User $user): bool
    {
        $me = auth()->user();

        return $me && ($me->id === $user->id || $me->can('Update user'));
    }

    private function authorizeEdit(User $user, bool $toEdit = true): void
    {
        $me = auth()->user();
        $allowed = $toEdit ? $this->canEdit($user) : ($me && ($me->id === $user->id || $me->can('View user') || $me->can('Update user')));
        abort_unless($allowed, 403);
    }
}
