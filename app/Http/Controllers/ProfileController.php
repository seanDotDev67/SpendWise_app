<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ProfilePhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'in:system,light,dark'],
            'reduced_motion' => ['sometimes', 'boolean'],
        ]);

        $request->user()->update([
            'theme' => $validated['theme'],
            'reduced_motion' => $request->boolean('reduced_motion'),
        ]);

        return Redirect::route('profile.edit')->with('status', 'preferences-updated');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $file = $validated['photo'];
        $contents = $file->get();
        if ($contents === false || $contents === '') {
            throw new RuntimeException('The profile photo could not be saved.');
        }

        ProfilePhoto::updateOrCreate(
            ['user_id' => $user->id],
            ['mime' => $file->getMimeType() ?: 'image/jpeg', 'data' => base64_encode($contents)],
        );

        $oldPath = $user->profile_photo_path;
        $user->profile_photo_path = 'db:'.now()->timestamp;
        $user->save();
        $this->deleteStoredPhoto($oldPath, $user->id);

        return Redirect::route('profile.edit')->with('status', 'profile-photo-updated');
    }

    public function showPhoto(Request $request): Response
    {
        $photo = ProfilePhoto::find($request->user()->id);
        abort_if($photo === null, 404);

        return response(base64_decode($photo->data), 200, [
            'Content-Type' => $photo->mime,
            'Cache-Control' => 'private, max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        $oldPath = $user->profile_photo_path;
        $user->profile_photo_path = null;
        $user->save();
        ProfilePhoto::where('user_id', $user->id)->delete();
        $this->deleteStoredPhoto($oldPath, $user->id);

        return Redirect::route('profile.edit')->with('status', 'profile-photo-deleted');
    }

    private function deleteStoredPhoto(?string $path, int $userId): void
    {
        if ($path === null || ! str_starts_with($path, 'profile-photos/'.$userId.'/')) {
            return;
        }

        if (! Storage::disk('public')->delete($path)) {
            Log::warning('Unable to remove a replaced profile photo.', ['user_id' => $userId]);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
