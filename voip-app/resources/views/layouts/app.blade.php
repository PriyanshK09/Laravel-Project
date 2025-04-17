<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SecureVoIP') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
    <style>
        /* Custom Encryption Colors - Added directly to make sure they're available */
        :root {
            --encryption-symmetric: #3b82f6;
            --encryption-symmetric-dark: #2563eb;
            --encryption-symmetric-light: #dbeafe;
            --encryption-asymmetric: #8b5cf6;
            --encryption-asymmetric-dark: #6d28d9;
            --encryption-asymmetric-light: #ede9fe;
        }
        .text-encryption-symmetric { color: var(--encryption-symmetric); }
        .text-encryption-symmetric-dark { color: var(--encryption-symmetric-dark); }
        .bg-encryption-symmetric { background-color: var(--encryption-symmetric); }
        .bg-encryption-symmetric-dark { background-color: var(--encryption-symmetric-dark); }
        .bg-encryption-symmetric-light { background-color: var(--encryption-symmetric-light); }
        
        .text-encryption-asymmetric { color: var(--encryption-asymmetric); }
        .text-encryption-asymmetric-dark { color: var(--encryption-asymmetric-dark); }
        .bg-encryption-asymmetric { background-color: var(--encryption-asymmetric); }
        .bg-encryption-asymmetric-dark { background-color: var(--encryption-asymmetric-dark); }
        .bg-encryption-asymmetric-light { background-color: var(--encryption-asymmetric-light); }
        
        /* Encryption badge style */
        .encryption-badge {
            display: inline-flex;
            align-items: center;
            padding: 0 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.25rem;
            border-radius: 9999px;
        }
        .encryption-badge.text-encryption-symmetric {
            background-color: var(--encryption-symmetric-light);
        }
        .encryption-badge.text-encryption-asymmetric {
            background-color: var(--encryption-asymmetric-light);
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100" x-data="{ open: false, userDropdownOpen: false }">
        <nav class="bg-white border-b border-gray-100 shadow-sm">
            <!-- Primary Navigation Menu -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('home') }}" class="flex items-center">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-2 flex items-center justify-center shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                </div>
                                <span class="ml-2 font-semibold text-lg tracking-tight text-gray-800">SecureVoIP</span>
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="{{ route('home') }}" 
                               class="{{ request()->routeIs('home') ? 'border-b-2 border-indigo-500 text-gray-900' : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out">
                                Dashboard
                            </a>
                            <a href="{{ route('calls.index') }}" 
                               class="{{ request()->routeIs('calls.*') ? 'border-b-2 border-indigo-500 text-gray-900' : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out">
                                Calls
                            </a>
                            <a href="{{ route('encryption.index') }}" 
                               class="{{ request()->routeIs('encryption.*') ? 'border-b-2 border-indigo-500 text-gray-900' : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out">
                                Encryption Keys
                            </a>
                        </div>
                    </div>

                    <!-- Settings Dropdown / Login/Register -->
                    @auth
                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <div class="ml-3 relative" x-data="{ open: false }">
                            <div>
                                <button @click="open = !open" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                    <div class="flex-shrink-0 h-8 w-8 bg-indigo-500 rounded-full flex items-center justify-center text-white font-semibold shadow-sm">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                </button>
                            </div>
                            <!-- Dropdown menu -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 @click.away="open = false"
                                 class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                 role="menu"
                                 aria-orientation="vertical"
                                 aria-labelledby="user-menu-button"
                                 tabindex="-1">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Profile</a>
                                <a href="{{ route('profile.security') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Security Settings</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Log Out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="hidden sm:flex sm:items-center sm:ml-6 space-x-4">
                        <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-gray-900">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 hover:text-gray-900">Register</a>
                        @endif
                    </div>
                    @endauth

                    <!-- Hamburger -->
                    <div class="-mr-2 flex items-center sm:hidden">
                        <button @click="open = !open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                        Dashboard
                    </a>
                    <a href="{{ route('calls.index') }}" class="{{ request()->routeIs('calls.*') ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                        Calls
                    </a>
                    <a href="{{ route('encryption.index') }}" class="{{ request()->routeIs('encryption.*') ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                        Encryption Keys
                    </a>
                </div>

                <!-- Responsive Settings Options -->
                @auth
                <div class="pt-4 pb-1 border-t border-gray-200">
                    <div class="px-4">
                        <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <a href="{{ route('profile.edit') }}" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300">
                            Your Profile
                        </a>
                        <a href="{{ route('profile.security') }}" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300">
                            Security Settings
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <div class="pt-4 pb-1 border-t border-gray-200 space-y-1 px-4">
                    <a href="{{ route('login') }}" class="block py-2 text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="block py-2 text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50">Register</a>
                    @endif
                </div>
                @endauth
            </div>
        </nav>

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-6">
                        <div class="p-4 rounded-md text-sm 
                            @if (session('status.type') === 'success')
                                bg-green-50 text-green-700
                            @elseif (session('status.type') === 'warning')
                                bg-yellow-50 text-yellow-700
                            @elseif (session('status.type') === 'error')
                                bg-red-50 text-red-700
                            @else
                                bg-blue-50 text-blue-700
                            @endif
                        ">
                            {{ session('status.message') }}
                        </div>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
        
        <footer class="bg-white border-t border-gray-200 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="flex justify-center md:order-2 space-x-6">
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">GitHub</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.48A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Twitter</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                            </svg>
                        </a>
                    </div>
                    <div class="mt-8 md:mt-0 md:order-1">
                        <p class="text-center text-gray-400 text-sm">
                            &copy; 2025 SecureVOIP. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Add alpine.js script reference -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Add alpine initialization script -->
    <script>
        document.addEventListener('alpine:init', () => {
            // Initialize VOIP functionality
            window.initializeVOIP = function(config = {}) {
                return {
                    callId: config.callId || null,
                    encryptionMethod: config.encryptionMethod || 'symmetric',
                    isRecipient: config.isRecipient || false,
                    sessionId: config.sessionId || null,
                    callStatus: 'connecting',
                    callActive: false,
                    callDuration: 0,
                    callTimer: null,
                    isMuted: false,
                    isVideoOff: false,
                    messages: [],
                    newMessage: '',
                    showEncryptionDetails: false,
                    
                    startCall() {
                        console.log('Starting call with ID:', this.callId);
                        this.callStatus = 'connecting';
                        this.startCallTimer();
                    },
                    
                    answerCall() {
                        console.log('Answering call with ID:', this.callId);
                        // In a real app, this would make an AJAX request to update call status
                        this.callStatus = 'connecting';
                        
                        // For demonstration, show that the call was answered
                        this.messages.push({
                            text: 'You answered the call',
                            type: 'system',
                            time: new Date().toISOString()
                        });
                    },
                    
                    endCall() {
                        console.log('Ending call with ID:', this.callId);
                        this.callStatus = 'ended';
                        this.callActive = false;
                        this.stopCallTimer();
                    },
                    
                    toggleMute() {
                        this.isMuted = !this.isMuted;
                        if (this.localStream) {
                            this.localStream.getAudioTracks().forEach(track => {
                                track.enabled = !this.isMuted;
                            });
                        }
                    },
                    
                    toggleVideo() {
                        this.isVideoOff = !this.isVideoOff;
                        if (this.localStream) {
                            this.localStream.getVideoTracks().forEach(track => {
                                track.enabled = !this.isVideoOff;
                            });
                        }
                    },
                    
                    startCallTimer() {
                        this.callTimer = setInterval(() => {
                            if (this.callStatus === 'connected') {
                                this.callDuration++;
                            }
                        }, 1000);
                    },
                    
                    stopCallTimer() {
                        if (this.callTimer) {
                            clearInterval(this.callTimer);
                            this.callTimer = null;
                        }
                    },
                    
                    formatDuration(seconds) {
                        const h = Math.floor(seconds / 3600);
                        const m = Math.floor((seconds % 3600) / 60);
                        const s = seconds % 60;
                        return [
                            h > 0 ? h.toString().padStart(2, '0') : null, 
                            m.toString().padStart(2, '0'), 
                            s.toString().padStart(2, '0')
                        ].filter(Boolean).join(':');
                    },
                    
                    encryptionColor(method) {
                        return method === 'symmetric' 
                            ? 'text-encryption-symmetric encryption-badge' 
                            : 'text-encryption-asymmetric encryption-badge';
                    },
                    
                    statusColor(status) {
                        return {
                            'connecting': 'bg-yellow-100 text-yellow-800',
                            'connected': 'bg-green-100 text-green-800',
                            'disconnected': 'bg-red-100 text-red-800',
                            'ended': 'bg-gray-100 text-gray-800'
                        }[status] || 'bg-gray-100 text-gray-800';
                    },
                    
                    toggleEncryptionDetails() {
                        this.showEncryptionDetails = !this.showEncryptionDetails;
                    },
                    
                    sendMessage() {
                        // This is a placeholder - the real implementation is in the WebRTC extension
                        console.log('Sending message:', this.newMessage);
                    }
                };
            };
        });
    </script>
    
    @stack('scripts')
</body>
</html>
