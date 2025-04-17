@extends('layouts.app')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Start a New Encrypted Call</h2>
        
        <form action="{{ route('calls.store') }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <label for="recipient_id" class="block text-sm font-medium text-gray-700 mb-1">Select Recipient</label>
                <select name="recipient_id" id="recipient_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                    <option value="">-- Select a user --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                
                @error('recipient_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Encryption Method</label>
                
                <div class="mt-2 space-y-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="encryption_symmetric" name="encryption_method" type="radio" value="symmetric" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300" checked>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="encryption_symmetric" class="font-medium text-gray-700">Symmetric Encryption</label>
                            <p class="text-gray-500">Uses a shared secret key for both encryption and decryption. Faster but requires secure key exchange.</p>
                            <div class="mt-1 text-xs text-gray-500">Algorithm: AES-256-CBC</div>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="encryption_asymmetric" name="encryption_method" type="radio" value="asymmetric" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="encryption_asymmetric" class="font-medium text-gray-700">Asymmetric Encryption</label>
                            <p class="text-gray-500">Uses public/private key pairs. More secure for initial communication and key exchange.</p>
                            <div class="mt-1 text-xs text-gray-500">Algorithm: RSA-2048</div>
                        </div>
                    </div>
                </div>
                
                @error('encryption_method')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex justify-end">
                <a href="{{ route('calls.index') }}" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-2">
                    Cancel
                </a>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Start Call
                </button>
            </div>
        </form>
    </div>
</div>

<div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">About Encryption Methods</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-blue-600">Symmetric Encryption</h4>
                <ul class="mt-2 list-disc list-inside text-sm text-gray-600 space-y-1">
                    <li>Both parties use the same key to encrypt and decrypt</li>
                    <li>More efficient for encrypting large volumes of data</li>
                    <li>Requires a secure method to exchange the key</li>
                    <li>Provides data confidentiality</li>
                    <li>Uses AES-256-CBC algorithm in this application</li>
                </ul>
            </div>
            
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-blue-600">Asymmetric Encryption</h4>
                <ul class="mt-2 list-disc list-inside text-sm text-gray-600 space-y-1">
                    <li>Uses a pair of mathematically related keys: public and private</li>
                    <li>Data encrypted with public key can only be decrypted with private key</li>
                    <li>No need for secure key exchange since public keys are shared openly</li>
                    <li>More resource-intensive than symmetric encryption</li>
                    <li>Uses RSA-2048 algorithm in this application</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection