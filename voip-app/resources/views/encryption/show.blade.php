@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header with navigation -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        @if($key->key_type === 'symmetric')
                            Symmetric Key
                        @elseif($key->key_type === 'public')
                            Public/Private Key Pair
                        @else
                            Private Key
                        @endif
                    </h2>
                    <p class="mt-1 text-gray-600">{{ $key->name }}</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('encryption.edit', $key->id) }}" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                        Rename
                    </a>
                    <a href="{{ route('encryption.index') }}" class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">
                        Back to Keys
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Key details -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Key Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b">
                            <h4 class="font-medium text-gray-700">Details</h4>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Name:</span>
                                <span class="text-sm font-medium">{{ $key->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Type:</span>
                                <span class="text-sm font-medium">
                                    @if($key->key_type === 'symmetric')
                                        <span class="text-encryption-symmetric encryption-badge">Symmetric</span>
                                    @elseif($key->key_type === 'public')
                                        <span class="text-encryption-asymmetric encryption-badge">Public</span>
                                    @else
                                        <span class="text-encryption-asymmetric encryption-badge">Private</span>
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Algorithm:</span>
                                <span class="text-sm font-medium">
                                    {{ $key->algorithm ?? ($key->key_type === 'symmetric' ? 'AES-256-CBC' : 'RSA-2048') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Created:</span>
                                <span class="text-sm font-medium">{{ $key->created_at->format('M d, Y H:i:s') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Status:</span>
                                <span class="text-sm font-medium">
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
                </div>

                <div>
                    <div class="border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b">
                            <h4 class="font-medium text-gray-700">Usage Information</h4>
                        </div>
                        <div class="p-4 space-y-4">
                            <div class="text-sm">
                                @if($key->key_type === 'symmetric')
                                    <p>This symmetric key uses the AES-256-CBC algorithm for encryption and decryption.</p>
                                    <p class="mt-2">Both parties use the same key for encrypting and decrypting data.</p>
                                @elseif($key->key_type === 'public')
                                    <p>This is your public key from an asymmetric RSA-2048 key pair.</p>
                                    <p class="mt-2">You share your public key with others so they can encrypt messages that only you can decrypt with your private key.</p>
                                @else
                                    <p>This is your private key from an asymmetric RSA-2048 key pair.</p>
                                    <p class="mt-2"><strong>Keep this key secret</strong>. It is used to decrypt messages that were encrypted with your public key.</p>
                                @endif
                            </div>
                            
                            <div class="border-t pt-3">
                                <h5 class="text-sm font-medium text-gray-700 mb-1">Compatible with:</h5>
                                <ul class="list-disc list-inside text-sm text-gray-600">
                                    <li>Secure VOIP calls</li>
                                    <li>End-to-end encrypted chat messages</li>
                                    <li>File encryption/decryption</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key data section -->
            <div class="mt-6 border rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b">
                    <h4 class="font-medium text-gray-700">Key Data (Sensitive Information)</h4>
                </div>
                <div class="p-4">
                    <div class="bg-gray-50 rounded-md">
                        <div x-data="{ showKey: false }" class="relative">
                            <div x-show="!showKey" class="p-4 text-center">
                                <p class="text-gray-500 text-sm">Key data is hidden for security</p>
                                <button @click="showKey = !showKey" class="mt-2 text-indigo-600 text-sm hover:text-indigo-900">
                                    Show Key Data
                                </button>
                            </div>
                            <div x-show="showKey" class="p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-700">Key Format: Base64</span>
                                    <button @click="showKey = !showKey" class="text-indigo-600 text-sm hover:text-indigo-900">
                                        Hide Key Data
                                    </button>
                                </div>
                                <div class="bg-gray-100 p-3 rounded font-mono text-xs overflow-x-auto break-all">
                                    {{ $key->key_data }}
                                </div>
                                
                                @if($key->key_type === 'symmetric' && $key->initialization_vector)
                                    <div class="mt-4">
                                        <p class="text-sm font-medium text-gray-700 mb-1">Initialization Vector</p>
                                        <div class="bg-gray-100 p-3 rounded font-mono text-xs overflow-x-auto break-all">
                                            {{ $key->initialization_vector }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none" x-show="!showKey">
                                <div class="bg-gray-100 bg-opacity-60 px-4 py-2 rounded-full shadow-sm">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <span class="text-sm text-gray-500">Protected</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <p class="text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block text-yellow-500 mr-1 -mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Never share your private or symmetric keys with anyone
                        </p>
                    </div>
                </div>
            </div>

            <!-- Related key section (for asymmetric keys) -->
            @if($relatedKey)
                <div class="mt-6 border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b">
                        <h4 class="font-medium text-gray-700">
                            @if($key->key_type === 'public')
                                Associated Private Key
                            @else
                                Associated Public Key
                            @endif
                        </h4>
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h5 class="text-sm font-medium">{{ $relatedKey->name }}</h5>
                                <p class="text-xs text-gray-500">Created: {{ $relatedKey->created_at->format('M d, Y') }}</p>
                            </div>
                            <a href="{{ route('encryption.show', $relatedKey->id) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                View Key
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Action buttons -->
            <div class="mt-6 flex justify-between">
                <div>
                    @if(!$key->active)
                        <form method="POST" action="{{ route('encryption.setActive', $key->id) }}">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded shadow-sm hover:bg-green-700">
                                Set as Active Key
                            </button>
                        </form>
                    @endif
                </div>
                <div>
                    <form method="POST" action="{{ route('encryption.destroy', $key->id) }}" onsubmit="return confirm('Are you sure you want to delete this encryption key? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded shadow-sm hover:bg-red-700">
                            Delete Key
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection