@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Edit Encryption Key</h2>
                    <p class="mt-1 text-gray-600">Update the name of your encryption key</p>
                </div>
                <a href="{{ route('encryption.show', $key->id) }}" class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">
                    Back to Key
                </a>
            </div>
        </div>
    </div>

    <!-- Edit form -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-6">Key Information</h3>
            
            <div class="mb-6 border rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b">
                    <h4 class="font-medium text-gray-700">Key Details</h4>
                </div>
                <div class="p-4 space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500">Key Type:</span>
                            <span class="ml-2 text-sm font-medium">
                                @if($key->key_type === 'symmetric')
                                    <span class="text-encryption-symmetric encryption-badge">Symmetric</span>
                                @elseif($key->key_type === 'public')
                                    <span class="text-encryption-asymmetric encryption-badge">Public</span>
                                @else
                                    <span class="text-encryption-asymmetric encryption-badge">Private</span>
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Created:</span>
                            <span class="ml-2 text-sm font-medium">{{ $key->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Algorithm:</span>
                        <span class="ml-2 text-sm font-medium">
                            {{ $key->algorithm ?? ($key->key_type === 'symmetric' ? 'AES-256-CBC' : 'RSA-2048') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Status:</span>
                        <span class="ml-2 text-sm font-medium">
                            @if($key->active)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Inactive
                                </span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="{{ route('encryption.update', $key->id) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Key Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $key->name) }}" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                        required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="border-t pt-6">
                    <div class="bg-blue-50 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    You can only change the name of this key. The key data itself cannot be modified for security reasons.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <a href="{{ route('encryption.show', $key->id) }}" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-2">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Key Name
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection