@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Dashboard Header -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-800">Welcome, {{ Auth::user()->name }}</h2>
            <p class="mt-1 text-gray-600">Your secure communications dashboard</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Quick Call Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg col-span-2">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Quick Call</h3>
                <div class="mt-5" x-data="{ recipient: '', encryptionMethod: 'symmetric', encryptionKeyId: '' }">
                    <form action="{{ route('calls.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="recipient" class="block text-sm font-medium text-gray-700">Recipient</label>
                            <div class="mt-1">
                                <select id="recipient" name="recipient_id" x-model="recipient" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="">Select a user</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Encryption Type</label>
                            <div class="mt-2 space-y-4">
                                <div class="flex items-center">
                                    <input id="encryption-symmetric" name="encryption_method" type="radio" x-model="encryptionMethod" value="symmetric" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                    <label for="encryption-symmetric" class="ml-3 block text-sm font-medium text-gray-700">
                                        <span class="flex items-center">
                                            <span>Symmetric Encryption</span>
                                            <span class="text-encryption-symmetric encryption-badge ml-2">AES-256</span>
                                        </span>
                                        <span class="text-xs text-gray-500">Both parties use the same encryption key</span>
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="encryption-asymmetric" name="encryption_method" type="radio" x-model="encryptionMethod" value="asymmetric" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                    <label for="encryption-asymmetric" class="ml-3 block text-sm font-medium text-gray-700">
                                        <span class="flex items-center">
                                            <span>Asymmetric Encryption</span>
                                            <span class="text-encryption-asymmetric encryption-badge ml-2">RSA-2048</span>
                                        </span>
                                        <span class="text-xs text-gray-500">Uses public/private key pair for enhanced security</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Encryption Key Selection -->
                        <div x-show="recipient && encryptionMethod === 'symmetric'">
                            <label for="encryption_key_id" class="block text-sm font-medium text-gray-700">Encryption Key</label>
                            <div class="mt-1">
                                <select id="encryption_key_id" name="encryption_key_id" x-model="encryptionKeyId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="">Select an encryption key</option>
                                    @foreach($symmetricKeys as $key)
                                        <option value="{{ $key->id }}">{{ $key->name }} (Created: {{ $key->created_at->format('Y-m-d') }})</option>
                                    @endforeach
                                    <option value="new">+ Create a new key</option>
                                </select>
                            </div>
                        </div>
                        
                        <div x-show="recipient && encryptionMethod === 'asymmetric'">
                            <label for="encryption_key_id_asymmetric" class="block text-sm font-medium text-gray-700">Key Pair</label>
                            <div class="mt-1">
                                <select id="encryption_key_id_asymmetric" name="encryption_key_id" x-model="encryptionKeyId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="">Select a key pair</option>
                                    @foreach($asymmetricKeys as $key)
                                        <option value="{{ $key->id }}">{{ $key->name }} (Created: {{ $key->created_at->format('Y-m-d') }})</option>
                                    @endforeach
                                    <option value="new">+ Generate a new key pair</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" :disabled="!recipient || !encryptionKeyId" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300 disabled:cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                Start Secure Call
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Encryption Keys -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">My Encryption Keys</h3>
                    <a href="{{ route('encryption.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">+ New Key</a>
                </div>
                <div class="mt-3 space-y-2">
                    @forelse($recentKeys as $key)
                        <div class="flex items-center justify-between p-2 border border-gray-100 rounded-md hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="mr-2">
                                    @if($key->key_type === 'symmetric')
                                        <span class="text-encryption-symmetric encryption-badge">AES</span>
                                    @else
                                        <span class="text-encryption-asymmetric encryption-badge">RSA</span>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $key->name }}</p>
                                    <p class="text-xs text-gray-500">Created {{ $key->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <a href="{{ route('encryption.show', $key->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-500">No encryption keys yet</p>
                            <a href="{{ route('encryption.create') }}" class="mt-2 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Create your first key
                            </a>
                        </div>
                    @endforelse
                    
                    @if(count($recentKeys) > 0)
                        <div class="pt-2 text-center">
                            <a href="{{ route('encryption.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                                View all keys
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Call History -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900">Recent Call History</h3>
            <div class="mt-5">
                <div class="flex flex-col">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Participant
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date & Time
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Duration
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Encryption
                                            </th>
                                            <th scope="col" class="relative px-6 py-3">
                                                <span class="sr-only">Actions</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($recentCalls as $call)
                                            @if($call->participant)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                                                <span class="text-indigo-700 text-sm font-medium">
                                                                    {{ substr($call->participant->name ?? 'U', 0, 1) }}
                                                                </span>
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900">
                                                                    {{ $call->participant->name ?? 'Unknown User' }}
                                                                </div>
                                                                <div class="text-sm text-gray-500">
                                                                    {{ $call->participant->email ?? 'No email' }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900">{{ $call->created_at->format('M d, Y') }}</div>
                                                        <div class="text-sm text-gray-500">{{ $call->created_at->format('h:i A') }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900">{{ $call->duration_formatted }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        @if($call->encryption_method === 'symmetric')
                                                            <span class="text-encryption-symmetric encryption-badge">AES-256</span>
                                                        @else
                                                            <span class="text-encryption-asymmetric encryption-badge">RSA-2048</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <a href="{{ route('calls.show', $call->id) }}" class="text-indigo-600 hover:text-indigo-900">Details</a>
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                                    No call history yet
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                @if(count($recentCalls) > 0)
                    <div class="pt-4 text-center">
                        <a href="{{ route('calls.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                            View full call history
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
