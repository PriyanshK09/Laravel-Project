@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Update Password</h2>
            
            <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required autocomplete="current-password" />
                    @error('current_password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" name="password" id="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required autocomplete="new-password" />
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required autocomplete="new-password" />
                    @error('password_confirmation')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="flex items-center justify-end">
                    <button type="submit" class="ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Security Settings</h2>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-md font-medium text-gray-900">Account Creation Date</h3>
                        <p class="text-sm text-gray-500">{{ $user->created_at->format('F j, Y') }}</p>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-md font-medium text-gray-900">Last Login</h3>
                        <p class="text-sm text-gray-500">{{ $user->updated_at->format('F j, Y') }}</p>
                    </div>
                </div>
                
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-md font-medium text-gray-900">Account Security Recommendations</h3>
                    <ul class="mt-2 text-sm text-gray-500 list-disc pl-5 space-y-1">
                        <li>Use a strong, unique password for your account</li>
                        <li>Change your password regularly</li>
                        <li>Enable two-factor authentication when available</li>
                        <li>Be careful about sharing sensitive information in your communications</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
