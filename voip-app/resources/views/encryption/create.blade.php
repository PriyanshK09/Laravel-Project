@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">New Encryption Key</h2>
                    <p class="mt-1 text-gray-600">Create a new encryption key for secure communications</p>
                </div>
                <a href="{{ route('encryption.index') }}" class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">
                    Back to Keys
                </a>
            </div>
        </div>
    </div>

    <!-- Creation form -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6" x-data="{ keyType: '{{ request('type') === 'asymmetric' ? 'asymmetric' : 'symmetric' }}' }">
            <h3 class="text-lg font-medium text-gray-900 mb-6">Key Creation</h3>
            
            <form method="POST" action="{{ route('encryption.store') }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Key Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                        placeholder="Enter a descriptive name for this key" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Key Type</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Symmetric Key Option -->
                        <div class="relative border rounded-lg p-4" :class="{ 'border-indigo-500 bg-indigo-50': keyType === 'symmetric', 'border-gray-300': keyType !== 'symmetric' }">
                            <label class="flex items-center">
                                <input type="radio" name="key_type" value="symmetric" x-model="keyType" 
                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" required>
                                <span class="ml-3 block font-medium text-gray-700">Symmetric Encryption</span>
                            </label>
                            
                            <div class="mt-2">
                                <span class="text-encryption-symmetric encryption-badge">AES-256-CBC</span>
                            </div>
                            
                            <p class="mt-3 text-sm text-gray-500">
                                Symmetric key encryption uses the same key for both encryption and decryption.
                            </p>
                            
                            <ul class="mt-2 text-xs text-gray-500 list-disc list-inside">
                                <li>Faster than asymmetric encryption</li>
                                <li>Suitable for encrypting large amounts of data</li>
                                <li>Requires secure key exchange</li>
                            </ul>
                        </div>
                        
                        <!-- Asymmetric Key Option -->
                        <div class="relative border rounded-lg p-4" :class="{ 'border-indigo-500 bg-indigo-50': keyType === 'asymmetric', 'border-gray-300': keyType !== 'asymmetric' }">
                            <label class="flex items-center">
                                <input type="radio" name="key_type" value="asymmetric" x-model="keyType" 
                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" required>
                                <span class="ml-3 block font-medium text-gray-700">Asymmetric Key Pair</span>
                            </label>
                            
                            <div class="mt-2">
                                <span class="text-encryption-asymmetric encryption-badge">RSA-2048</span>
                            </div>
                            
                            <p class="mt-3 text-sm text-gray-500">
                                Asymmetric encryption uses a pair of keys: public (for encryption) and private (for decryption).
                            </p>
                            
                            <ul class="mt-2 text-xs text-gray-500 list-disc list-inside">
                                <li>More secure key exchange</li>
                                <li>Public key can be freely shared</li>
                                <li>Private key must be kept secret</li>
                            </ul>
                        </div>
                    </div>
                    @error('key_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Key Details -->
                <div class="border-t pt-6">
                    <h4 class="font-medium text-gray-900 mb-4">Key Details</h4>
                    
                    <!-- Symmetric Key Details -->
                    <div x-show="keyType === 'symmetric'">
                        <div class="bg-blue-50 p-4 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1 md:flex md:justify-between">
                                    <p class="text-sm text-blue-700">
                                        A new AES-256-CBC symmetric key will be generated. This key will be automatically set as your active symmetric key.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Asymmetric Key Details -->
                    <div x-show="keyType === 'asymmetric'">
                        <div class="bg-purple-50 p-4 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1 md:flex md:justify-between">
                                    <p class="text-sm text-purple-700">
                                        A new RSA-2048 public/private key pair will be generated. Both keys will be automatically set as your active asymmetric keys.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <h4 class="font-medium text-gray-900 mb-4">Security Note</h4>
                    
                    <div class="bg-yellow-50 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Keep your keys secure. Never share your private or symmetric keys with anyone. Your keys are stored encrypted in our database.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <a href="{{ route('encryption.index') }}" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-2">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Generate Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection