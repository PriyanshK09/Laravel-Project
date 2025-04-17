@extends('layouts.app')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Your Calls</h2>
            <a href="{{ route('calls.create') }}" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">New Call</a>
        </div>

        @if($calls->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session ID</th>
                            <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">With</th>
                            <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Encryption</th>
                            <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                            <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                            <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($calls as $call)
                            <tr>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ substr($call->session_id, 0, 8) }}...</div>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if(Auth::id() == $call->caller_id)
                                        <div class="text-sm text-gray-900">{{ $call->recipient->name }}</div>
                                        <div class="text-xs text-gray-500">Outgoing</div>
                                    @else
                                        <div class="text-sm text-gray-900">{{ $call->caller->name }}</div>
                                        <div class="text-xs text-gray-500">Incoming</div>
                                    @endif
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="encryption-badge {{ $call->encryption_method === 'symmetric' ? 'encryption-symmetric' : 'encryption-asymmetric' }}">
                                        {{ ucfirst($call->encryption_method) }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full call-status-{{ $call->status }}">
                                        {{ ucfirst($call->status) }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $call->started_at ? $call->started_at->format('M d, H:i') : 'Not started' }}
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($call->getDuration())
                                        {{ gmdate('H:i:s', $call->getDuration()) }}
                                    @else
                                        --:--:--
                                    @endif
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('calls.show', $call->id) }}" class="text-blue-600 hover:text-blue-900">
                                            @if($call->isActive())
                                                Join
                                            @else
                                                View
                                            @endif
                                        </a>
                                        
                                        <form method="POST" action="{{ route('calls.destroy', $call->id) }}" onsubmit="return confirm('Are you sure you want to remove this call from history?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $calls->links() }}
            </div>
        @else
            <div class="text-center py-10">
                <p class="text-gray-500">No calls found in your history.</p>
                <a href="{{ route('calls.create') }}" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Make a call</a>
            </div>
        @endif
    </div>
</div>
@endsection