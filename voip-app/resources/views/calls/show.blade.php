@extends('layouts.app')

@section('content')
<div 
    x-data="initializeVOIP({
        callId: '{{ $call->id }}', 
        encryptionMethod: '{{ $call->encryption_method }}',
        isRecipient: {{ $isRecipient ? 'true' : 'false' }},
        sessionId: '{{ $call->session_id }}'
    })"
    x-init="startCall()"
    class="space-y-4"
>
    <!-- Call header -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-4 border-b border-gray-200 flex flex-col md:flex-row md:items-center justify-between bg-gray-50">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-12 w-12 bg-gray-300 rounded-full flex items-center justify-center mr-4">
                    <span class="text-lg font-semibold text-gray-600">{{ substr($otherUser->name, 0, 2) }}</span>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Call with {{ $otherUser->name }}</h2>
                    <div class="flex items-center mt-1 space-x-2">
                        <span class="encryption-badge" :class="encryptionColor(encryptionMethod)">
                            <template x-if="encryptionMethod === 'symmetric'">Symmetric</template>
                            <template x-if="encryptionMethod === 'asymmetric'">Asymmetric</template>
                            Encrypted
                        </span>
                        
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="statusColor(callStatus)">
                            <span x-text="callStatus.charAt(0).toUpperCase() + callStatus.slice(1)"></span>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 md:mt-0 flex items-center space-x-4">
                <span class="text-sm bg-white px-3 py-1 rounded-full border border-gray-200 shadow-sm flex items-center">
                    <svg class="w-4 h-4 text-gray-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span x-text="formatDuration(callDuration)"></span>
                </span>
                
                <div class="relative">
                    <button @click="toggleEncryptionDetails" class="bg-white p-2 rounded-full border border-gray-200 shadow-sm hover:bg-gray-50">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                    
                    <div x-show="showEncryptionDetails" @click.away="showEncryptionDetails = false" class="absolute right-0 mt-2 w-72 bg-white rounded-md shadow-lg p-4 z-10">
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Encryption Details</h3>
                        <div class="text-xs space-y-2 text-gray-600">
                            <div class="flex justify-between">
                                <span>Method:</span>
                                <span class="font-medium" x-text="encryptionMethod === 'symmetric' ? 'Symmetric (AES-256-CBC)' : 'Asymmetric (RSA-2048)'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Session ID:</span>
                                <span class="font-mono text-xs">{{ Str::limit($call->session_id, 16) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Key Exchange:</span>
                                <span class="font-medium" id="keyExchangeStatus">Pending</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Call Start:</span>
                                <span>{{ $call->started_at ? $call->started_at->format('M d, H:i:s') : 'Not started' }}</span>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                <p x-show="encryptionMethod === 'symmetric'">All communication is encrypted using a shared secret key with AES-256-CBC algorithm.</p>
                                <p x-show="encryptionMethod === 'asymmetric'">Messages are encrypted with the recipient's public key and can only be decrypted with their private key.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @if($call->status === 'initiated' && $isRecipient)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        Incoming call from {{ $call->caller->name }}
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>You're receiving a call with {{ $call->encryption_method }} encryption. Accept to continue.</p>
                    </div>
                    <div class="mt-4">
                        <div class="-mx-2 -my-1.5 flex">
                            <button @click="answerCall()" class="bg-yellow-50 px-4 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-yellow-50 focus:ring-yellow-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 3h5m0 0v5m0-5l-6 6M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"></path>
                                </svg>
                                Accept Call
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Main call area -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Video container -->
        <div class="md:col-span-2">
            <div class="bg-gray-800 rounded-lg overflow-hidden relative" style="min-height: 480px;">
                <!-- Remote video -->
                <video id="remoteVideo" class="w-full h-full object-cover" autoplay playsinline></video>
                
                <!-- Video overlay status when not connected -->
                <div x-show="callStatus !== 'connected'" class="absolute inset-0 flex items-center justify-center bg-gray-900 bg-opacity-60 text-white">
                    <div class="text-center">
                        <div x-show="callStatus === 'connecting'" class="animate-pulse">
                            <svg class="w-16 h-16 mx-auto text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                            </svg>
                            <p class="mt-4 text-xl font-medium">Connecting to {{ $otherUser->name }}...</p>
                            <p class="text-sm opacity-75 mt-2">Establishing secure {{ $call->encryption_method }} connection</p>
                        </div>
                        <div x-show="callStatus === 'disconnected'" class="text-red-300">
                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"></path>
                            </svg>
                            <p class="mt-4 text-xl font-medium">Disconnected</p>
                            <p class="text-sm opacity-75 mt-2">Connection lost or ended</p>
                        </div>
                        <div x-show="callStatus === 'ended'" class="text-gray-300">
                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"></path>
                            </svg>
                            <p class="mt-4 text-xl font-medium">Call Ended</p>
                            <p class="text-sm opacity-75 mt-2">Duration: <span x-text="formatDuration(callDuration)"></span></p>
                        </div>
                    </div>
                </div>
                
                <!-- Local video (small overlay) -->
                <div class="absolute bottom-4 right-4 w-1/4 rounded-lg overflow-hidden border-2 border-white shadow-lg">
                    <video id="localVideo" class="w-full h-full object-cover" autoplay muted playsinline></video>
                </div>
                
                <!-- Call controls -->
                <div class="absolute bottom-4 left-0 right-0 flex justify-center space-x-4">
                    <button 
                        x-on:click="toggleMute()"
                        class="p-3 rounded-full shadow-md transition-all duration-200"
                        :class="isMuted ? 'bg-gray-700 text-red-500' : 'bg-white text-gray-800 hover:bg-gray-100'"
                    >
                        <svg x-show="!isMuted" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                        <svg x-show="isMuted" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path>
                        </svg>
                    </button>
                    
                    <button 
                        x-on:click="toggleVideo()"
                        class="p-3 rounded-full shadow-md transition-all duration-200"
                        :class="isVideoOff ? 'bg-gray-700 text-red-500' : 'bg-white text-gray-800 hover:bg-gray-100'"
                    >
                        <svg x-show="!isVideoOff" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <svg x-show="isVideoOff" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9l4 4m0-4l-4 4"></path>
                        </svg>
                    </button>
                    
                    <form method="POST" action="{{ route('calls.update', $call->id) }}" onsubmit="return confirm('Are you sure you want to end this call?')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="end">
                        <button 
                            type="submit"
                            x-on:click="endCall()"
                            class="bg-red-600 p-3 rounded-full shadow-md text-white hover:bg-red-700 transition-all duration-200"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Connection status -->
            <div class="mt-3 p-3 bg-gray-50 border border-gray-200 rounded-md shadow-sm">
                <div class="flex items-center text-sm">
                    <div class="flex-shrink-0 mr-2">
                        <span 
                            class="h-3 w-3 rounded-full inline-block"
                            :class="{
                                'bg-yellow-400 animate-pulse': callStatus === 'connecting',
                                'bg-green-500': callStatus === 'connected',
                                'bg-red-500': callStatus === 'disconnected',
                                'bg-gray-400': callStatus === 'ended'
                            }"
                        ></span>
                    </div>
                    <div>
                        <span 
                            x-text="{
                                'connecting': 'Establishing secure connection...',
                                'connected': 'Connected with end-to-end encryption',
                                'disconnected': 'Connection lost',
                                'ended': 'Call ended'
                            }[callStatus]"
                        ></span>
                        <span x-show="callStatus === 'connected'" class="ml-1 text-xs text-gray-500">
                            (<span x-text="encryptionMethod === 'symmetric' ? 'AES-256-CBC' : 'RSA-2048'"></span>)
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Encrypted chat sidebar -->
        <div class="md:col-span-1 flex flex-col">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden h-full flex flex-col">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium text-gray-700">Encrypted Chat</h3>
                        <span class="text-xs px-2 py-1 rounded-full" :class="encryptionColor(encryptionMethod)">
                            <span x-text="encryptionMethod === 'symmetric' ? 'AES-256' : 'RSA'"></span>
                        </span>
                    </div>
                </div>
                
                <!-- Messages -->
                <div id="chatMessages" class="flex-grow overflow-y-auto p-4 space-y-3">
                    <template x-if="messages.length === 0">
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                </svg>
                                <p class="mt-2">No messages yet</p>
                                <p class="text-xs mt-1">Messages are end-to-end encrypted</p>
                            </div>
                        </div>
                    </template>
                    
                    <template x-for="(message, index) in messages" :key="index">
                        <div :class="{'flex justify-end': message.type === 'sent', 'flex justify-start': message.type === 'received', 'flex justify-center': message.type === 'system' || message.type === 'error'}">
                            <div :class="{
                                'bg-blue-100 text-blue-800 rounded-lg py-2 px-3 max-w-[85%]': message.type === 'sent',
                                'bg-gray-100 text-gray-800 rounded-lg py-2 px-3 max-w-[85%]': message.type === 'received',
                                'bg-gray-100 text-gray-500 text-xs rounded-md py-1 px-2 max-w-[90%]': message.type === 'system',
                                'bg-red-50 text-red-500 text-xs rounded-md py-1 px-2 max-w-[90%]': message.type === 'error'
                            }">
                                <div x-text="message.text"></div>
                                <div :class="{
                                    'text-xs text-blue-600 text-right mt-1': message.type === 'sent',
                                    'text-xs text-gray-600 text-right mt-1': message.type === 'received',
                                    'hidden': message.type === 'system' || message.type === 'error'
                                }">
                                    <span x-text="message.type === 'sent' ? 'You' : '{{ $otherUser->name }}'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Message input -->
                <div class="border-t border-gray-200 p-3 bg-gray-50">
                    <form @submit.prevent="sendMessage" class="flex">
                        <input 
                            x-model="newMessage"
                            type="text" 
                            class="flex-grow rounded-l-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                            placeholder="Type encrypted message..."
                            :disabled="callStatus !== 'connected'"
                        >
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:bg-blue-400"
                            :disabled="callStatus !== 'connected' || !newMessage.trim()"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </form>
                    <div class="mt-2 text-center">
                        <span class="text-xs text-gray-500 flex items-center justify-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            End-to-end encrypted
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // WebRTC implementation
    document.addEventListener('alpine:init', () => {
        // This extends the Alpine VOIP functionality with WebRTC specifics
        const extendVoip = (voip) => {
            // STUN servers for WebRTC
            const iceServers = {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' }
                ]
            };
            
            // Initialize WebRTC connection
            voip.initializeWebRTC = async function() {
                try {
                    // Get user media (camera and microphone)
                    this.localStream = await navigator.mediaDevices.getUserMedia({ 
                        video: true, 
                        audio: true 
                    });
                    
                    // Display local video
                    const localVideo = document.getElementById('localVideo');
                    if (localVideo) {
                        localVideo.srcObject = this.localStream;
                    }
                    
                    // Create RTCPeerConnection
                    this.peerConnection = new RTCPeerConnection(iceServers);
                    
                    // Add local tracks to connection
                    this.localStream.getTracks().forEach(track => {
                        this.peerConnection.addTrack(track, this.localStream);
                    });
                    
                    // Handle incoming tracks (video/audio)
                    this.peerConnection.ontrack = event => {
                        if (event.streams && event.streams[0]) {
                            const remoteVideo = document.getElementById('remoteVideo');
                            if (remoteVideo) {
                                remoteVideo.srcObject = event.streams[0];
                            }
                        }
                    };
                    
                    // Create data channel for encrypted chat
                    this.dataChannel = this.peerConnection.createDataChannel('encryptedChat');
                    this.setupDataChannel(this.dataChannel);
                    
                    // Handle incoming data channels
                    this.peerConnection.ondatachannel = event => {
                        this.dataChannel = event.channel;
                        this.setupDataChannel(this.dataChannel);
                    };
                    
                    // ICE candidate handling
                    this.peerConnection.onicecandidate = event => {
                        if (event.candidate) {
                            // Send candidate to peer via signaling server
                            this.sendSignalingData({
                                type: 'candidate',
                                candidate: event.candidate
                            });
                        }
                    };
                    
                    // Connection state changes
                    this.peerConnection.onconnectionstatechange = () => {
                        if (this.peerConnection.connectionState === 'connected') {
                            this.callStatus = 'connected';
                        } else if (this.peerConnection.connectionState === 'disconnected' || 
                                   this.peerConnection.connectionState === 'failed') {
                            this.callStatus = 'disconnected';
                        } else if (this.peerConnection.connectionState === 'closed') {
                            this.callStatus = 'ended';
                        }
                    };
                    
                    // Exchange encryption keys
                    await this.exchangeEncryptionKeys();
                    
                    // Create or answer offer based on role
                    if (!this.isRecipient) {
                        // Create offer (caller)
                        const offer = await this.peerConnection.createOffer();
                        await this.peerConnection.setLocalDescription(offer);
                        
                        // Send offer to recipient via signaling server
                        this.sendSignalingData({
                            type: 'offer',
                            sdp: this.peerConnection.localDescription
                        });
                    }
                    
                    // For demonstration purposes in this mock implementation,
                    // we'll simulate a successful connection after a delay
                    setTimeout(() => {
                        if (this.callStatus === 'connecting') {
                            this.callStatus = 'connected';
                            this.callActive = true;
                            
                            // Add a welcome message to chat
                            this.messages.push({
                                text: `Secure ${this.encryptionMethod} encrypted connection established`,
                                type: 'system',
                                time: new Date().toISOString()
                            });
                            
                            // Update key exchange status
                            document.getElementById('keyExchangeStatus').textContent = 'Completed';
                            document.getElementById('keyExchangeStatus').classList.add('text-green-600');
                        }
                    }, 3000);
                    
                } catch (error) {
                    console.error('Error initializing WebRTC:', error);
                    this.callStatus = 'disconnected';
                }
            };
            
            // Setup data channel for encrypted chat
            voip.setupDataChannel = function(channel) {
                channel.onopen = () => {
                    console.log('Data channel is open');
                };
                
                channel.onclose = () => {
                    console.log('Data channel is closed');
                };
                
                channel.onmessage = async event => {
                    try {
                        // In a real implementation, this would decrypt the message
                        // For this mock, we'll simulate receiving an encrypted message
                        
                        // Simulate decryption delay
                        await new Promise(resolve => setTimeout(resolve, 300));
                        
                        const fakeDecryptedMessage = `Message from ${this.isRecipient ? 'caller' : 'recipient'}`;
                        
                        this.messages.push({
                            text: fakeDecryptedMessage,
                            type: 'received',
                            time: new Date().toISOString()
                        });
                    } catch (error) {
                        console.error('Error processing message:', error);
                        this.messages.push({
                            text: 'Failed to decrypt message',
                            type: 'error',
                            time: new Date().toISOString()
                        });
                    }
                };
            };
            
            // Exchange encryption keys with server
            voip.exchangeEncryptionKeys = async function() {
                try {
                    // In a real implementation, this would make an AJAX request to the server
                    // For this mock, we'll simulate a successful key exchange
                    
                    // Simulate network delay
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    
                    // Update key exchange status to show progress
                    const keyExchangeStatus = document.getElementById('keyExchangeStatus');
                    if (keyExchangeStatus) {
                        keyExchangeStatus.textContent = 'In Progress';
                        keyExchangeStatus.classList.add('text-yellow-600');
                    }
                    
                    // Add info message
                    this.messages.push({
                        text: `Exchanging ${this.encryptionMethod} encryption keys...`,
                        type: 'system',
                        time: new Date().toISOString()
                    });
                    
                    return {
                        success: true,
                        encryption_method: this.encryptionMethod
                    };
                } catch (error) {
                    console.error('Key exchange error:', error);
                    return { success: false, error: 'Failed to exchange keys' };
                }
            };
            
            // Handle signaling data (in a real app, this would use WebSockets)
            voip.sendSignalingData = function(data) {
                // This is a placeholder for sending signaling data
                // In a real app, this would send data to a signaling server
                console.log('Signaling data sent:', data);
                
                // For demonstration, we'll just show a message
                this.messages.push({
                    text: `Signaling: ${data.type} sent`,
                    type: 'system',
                    time: new Date().toISOString()
                });
                
                // Simulate receiving an answer if we sent an offer
                if (data.type === 'offer' && !this.isRecipient) {
                    setTimeout(() => {
                        this.processSignalingData({
                            type: 'answer',
                            sdp: {
                                type: 'answer',
                                sdp: 'v=0\r\no=- 0 0 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\na=group:BUNDLE 0\r\na=msid-semantic:WMS\r\nm=application 9 UDP/TLS/RTP/SAVPF 0\r\nc=IN IP4 0.0.0.0\r\na=ice-ufrag:0\r\na=ice-pwd:0\r\na=ice-options:trickle\r\na=fingerprint:sha-256 00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00\r\na=setup:active\r\na=mid:0\r\na=sctp-port:5000\r\na=max-message-size:262144\r\n'
                            }
                        });
                    }, 1500);
                }
            };
            
            // Process incoming signaling data
            voip.processSignalingData = async function(data) {
                try {
                    if (data.type === 'offer' && this.isRecipient) {
                        await this.peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp));
                        const answer = await this.peerConnection.createAnswer();
                        await this.peerConnection.setLocalDescription(answer);
                        
                        // Send answer to caller via signaling server
                        this.sendSignalingData({
                            type: 'answer',
                            sdp: this.peerConnection.localDescription
                        });
                        
                    } else if (data.type === 'answer' && !this.isRecipient) {
                        await this.peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp));
                        
                    } else if (data.type === 'candidate') {
                        await this.peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
                    }
                } catch (error) {
                    console.error('Error processing signaling data:', error);
                }
            };
            
            // Override the startCall method to initialize WebRTC
            const originalStartCall = voip.startCall;
            voip.startCall = function() {
                originalStartCall.call(this);
                this.initializeWebRTC();
            };
            
            // Override the answerCall method
            const originalAnswerCall = voip.answerCall;
            voip.answerCall = function() {
                originalAnswerCall.call(this);
                this.initializeWebRTC();
            };
            
            // Override the endCall method to clean up WebRTC resources
            const originalEndCall = voip.endCall;
            voip.endCall = function() {
                originalEndCall.call(this);
                
                // Close data channel
                if (this.dataChannel) {
                    this.dataChannel.close();
                }
                
                // Close peer connection
                if (this.peerConnection) {
                    this.peerConnection.close();
                }
            };
            
            // Override the sendMessage method for WebRTC data channel
            voip.sendMessage = async function() {
                if (!this.newMessage.trim()) return;
                
                const message = this.newMessage.trim();
                this.newMessage = '';
                
                try {
                    // Simulate encryption process
                    this.messages.push({
                        text: message,
                        type: 'sent',
                        time: new Date().toISOString()
                    });
                    
                    // In a real app, we would encrypt the message here
                    await new Promise(resolve => setTimeout(resolve, 200)); // Simulate encryption delay
                    
                    if (this.dataChannel && this.dataChannel.readyState === 'open') {
                        // In a real app, we would send the encrypted message through the data channel
                        // For this mock, we'll just log it and simulate receiving a response
                        console.log('Sending message:', message);
                        
                        // Simulate the peer's response
                        setTimeout(() => {
                            this.messages.push({
                                text: `This is an auto-response to your message. Using ${this.encryptionMethod} encryption.`,
                                type: 'received',
                                time: new Date().toISOString()
                            });
                        }, 2000);
                    } else {
                        this.messages.push({
                            text: 'Cannot send message: Data channel not open',
                            type: 'error',
                            time: new Date().toISOString()
                        });
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    this.messages.push({
                        text: 'Failed to send encrypted message',
                        type: 'error',
                        time: new Date().toISOString()
                    });
                }
                
                // Scroll to bottom of messages
                this.$nextTick(() => {
                    const container = document.getElementById('chatMessages');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                });
            };
            
            return voip;
        };
        
        // Enhance the initializeVOIP function
        const originalInitializeVOIP = window.initializeVOIP;
        window.initializeVOIP = function(config) {
            const voip = originalInitializeVOIP.call(this, config);
            return extendVoip(voip);
        };
    });
</script>
@endpush
@endsection