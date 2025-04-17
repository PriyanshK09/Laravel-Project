import './bootstrap';
import Alpine from 'alpinejs';

// Make Alpine available globally
window.Alpine = Alpine;

// VOIP-specific functionality
window.initializeVOIP = function() {
    return {
        // Call state
        callActive: false,
        callStatus: 'disconnected', // disconnected, connecting, connected, ended
        callDuration: 0,
        encryptionMethod: null,
        durationInterval: null,
        isMuted: false,
        isVideoOff: false,
        showEncryptionDetails: false,
        
        // Media elements
        localStream: null,
        remoteStream: null,
        peerConnection: null,
        dataChannel: null,
        
        // Messages
        messages: [],
        newMessage: '',
        
        init(config = {}) {
            // Set configuration from backend if provided
            this.callId = config.callId || null;
            this.encryptionMethod = config.encryptionMethod || 'symmetric';
            this.isRecipient = config.isRecipient || false;
            this.sessionId = config.sessionId || null;
            this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Setup screen recording elements if needed
            this.setupMediaElements();
            
            // Set up call events
            this.setupCallEvents();
        },
        
        setupMediaElements() {
            // Implement on call page only
            if (document.getElementById('localVideo')) {
                this.$watch('callActive', (isActive) => {
                    if (isActive) {
                        this.startCallTimer();
                    } else {
                        this.stopCallTimer();
                    }
                });
            }
        },
        
        setupCallEvents() {
            // Global events for call notifications
            window.addEventListener('incoming-call', (event) => {
                this.handleIncomingCall(event.detail);
            });
            
            window.addEventListener('call-ended', () => {
                this.endCall();
            });
        },
        
        // Call management methods
        startCall() {
            this.callStatus = 'connecting';
            this.callActive = true;
            
            // In a real implementation, this would trigger the WebRTC connection
            setTimeout(() => {
                this.callStatus = 'connected';
            }, 1500);
        },
        
        answerCall() {
            this.callStatus = 'connecting';
            this.callActive = true;
            
            // In a real implementation, this would accept the WebRTC connection
            setTimeout(() => {
                this.callStatus = 'connected';
            }, 1500);
        },
        
        endCall() {
            // In a real implementation, this would close the WebRTC connection
            this.callStatus = 'ended';
            this.callActive = false;
            this.stopCallTimer();
            
            // Cleanup streams
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => track.stop());
            }
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
        
        // Call timer
        startCallTimer() {
            const startTime = Date.now();
            this.callDuration = 0;
            
            this.durationInterval = setInterval(() => {
                this.callDuration = Math.floor((Date.now() - startTime) / 1000);
            }, 1000);
        },
        
        stopCallTimer() {
            if (this.durationInterval) {
                clearInterval(this.durationInterval);
                this.durationInterval = null;
            }
        },
        
        formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            return [
                hours > 0 ? String(hours).padStart(2, '0') : null,
                String(minutes).padStart(2, '0'),
                String(secs).padStart(2, '0')
            ].filter(Boolean).join(':');
        },
        
        // Chat functionality
        async sendMessage() {
            if (!this.newMessage.trim()) return;
            
            if (this.dataChannel && this.dataChannel.readyState === 'open') {
                try {
                    // In a real implementation, this would encrypt and send via WebRTC
                    const message = {
                        text: this.newMessage,
                        type: 'sent',
                        time: new Date().toISOString()
                    };
                    
                    this.messages.push(message);
                    this.newMessage = '';
                    
                    // Simulate encryption notice
                    this.messages.push({
                        text: `Message encrypted using ${this.encryptionMethod}`,
                        type: 'system',
                        time: new Date().toISOString()
                    });
                } catch (error) {
                    this.messages.push({
                        text: 'Failed to send encrypted message',
                        type: 'error',
                        time: new Date().toISOString()
                    });
                }
            } else {
                this.messages.push({
                    text: 'Cannot send message: Connection not established',
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
        },
        
        // Encryption visualization
        toggleEncryptionDetails() {
            this.showEncryptionDetails = !this.showEncryptionDetails;
        },
        
        encryptionColor(method) {
            return method === 'symmetric' ? 'bg-encryption-symmetric-light text-encryption-symmetric' : 'bg-encryption-asymmetric-light text-encryption-asymmetric';
        },
        
        statusColor(status) {
            const colors = {
                'disconnected': 'bg-gray-200 text-gray-700',
                'connecting': 'bg-yellow-100 text-yellow-800',
                'connected': 'bg-green-100 text-green-800',
                'ended': 'bg-gray-100 text-gray-600'
            };
            return colors[status] || colors.disconnected;
        }
    };
};

// Initialize Alpine
Alpine.start();
