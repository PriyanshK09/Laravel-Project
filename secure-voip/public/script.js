// Authentication variables
let jwt = localStorage.getItem('jwt');
let userId = localStorage.getItem('userId');
let pollingInterval = 2000;

// API configuration - point to the Laravel backend server
const API_BASE_URL = 'http://127.0.0.1:8000';

// DOM elements
const authForms = document.getElementById('authForms');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const callUI = document.getElementById('callUI');
const incomingCallScreen = document.getElementById('incomingCallScreen');

// Auth elements
const loginBtn = document.getElementById('loginBtn');
const registerBtn = document.getElementById('registerBtn');
const showRegisterBtn = document.getElementById('showRegisterBtn');
const showLoginBtn = document.getElementById('showLoginBtn');
const logoutBtn = document.getElementById('logoutBtn');

// Call elements
let localStream, remoteStream;
let peerConnection;
const peerIdInput = document.getElementById('peerId');
const startCallBtn = document.getElementById('startCall');
const localVideo = document.getElementById('localVideo');
const remoteVideo = document.getElementById('remoteVideo');
const callerIDElement = document.getElementById('callerID');
const acceptCallBtn = document.getElementById('acceptCall');
const rejectCallBtn = document.getElementById('rejectCall');
const callControls = document.getElementById('callControls');
const muteAudioBtn = document.getElementById('muteAudio');
const muteVideoBtn = document.getElementById('muteVideo');
const endCallBtn = document.getElementById('endCall');

// Message elements
const messageSystem = document.getElementById('messageSystem');
const messageContainer = document.getElementById('messageContainer');
const messageInput = document.getElementById('messageInput');
const sendMessageBtn = document.getElementById('sendMessage');

// DOM elements for call status
const callStatusOverlay = document.getElementById('callStatusOverlay');
const callStatusText = document.getElementById('callStatusText');
const callEndedOverlay = document.getElementById('callEndedOverlay');
const endedCallName = document.getElementById('endedCallName');
const callAgainBtn = document.getElementById('callAgainBtn');
const closeCallEndedBtn = document.getElementById('closeCallEndedBtn');
const remoteUserName = document.getElementById('remoteUserName');
const localStatus = document.getElementById('localStatus');
const remoteStatus = document.getElementById('remoteStatus');

// Encryption elements and variables
const encryptionMethodSelect = document.getElementById('encryptionMethod');
const refreshEncryptionBtn = document.getElementById('refreshEncryption');
const encryptionStatus = document.getElementById('encryptionStatus');
let currentEncryptionMethod = 'aes'; // Default to AES
let encryptionKeys = {
    aes: null,       // For symmetric encryption
    publicKey: null, // Our public key for asymmetric
    privateKey: null,// Our private key (stored server-side)
    peerPublicKey: null // Peer's public key
};
let isEncryptionReady = false;

// Call state variables
let targetPeerId = null;
let remotePeerName = "Remote User"; // Default name until we get the real one
let isInCall = false;
let hasPendingOffer = false;
let pendingOffer = null;
let callState = "idle"; // idle, connecting, ringing, connected, ended
const servers = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

// Data channel for messaging
let dataChannel = null;
let isDataChannelOpen = false;

// API wrapper function
async function apiCall(endpoint, method = 'GET', data = null) {
    // Ensure endpoint doesn't start with a slash and join with base URL
    const url = `${API_BASE_URL}/api/${endpoint.replace(/^\/+/, '')}`;
    
    console.log('Making API call to:', url);
    
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    };
    
    if (jwt) {
        options.headers['Authorization'] = `Bearer ${jwt}`;
    }
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    const response = await fetch(url, options);
    
    if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`API call failed: ${response.status} ${errorText}`);
    }
    
    return response.json();
}

// Show appropriate UI based on auth status
function updateUI() {
    if (jwt && userId) {
        authForms.style.display = 'none';
        callUI.style.display = 'block';
        // Show user ID on the call UI
        addUserIdDisplay();
        // Initialize encryption
        initializeEncryption();
    } else {
        authForms.style.display = 'block';
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        callUI.style.display = 'none';
        // Remove user ID display if it exists
        const userIdDisplay = document.getElementById('userIdDisplay');
        if (userIdDisplay) {
            userIdDisplay.remove();
        }
    }
}

// Auth form toggle events
showRegisterBtn.onclick = (e) => {
    e.preventDefault();
    loginForm.style.display = 'none';
    registerForm.style.display = 'block';
};

showLoginBtn.onclick = (e) => {
    e.preventDefault();
    registerForm.style.display = 'none';
    loginForm.style.display = 'block';
};

// Login handler
loginBtn.onclick = async () => {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    try {
        const data = await apiCall('login', 'POST', { email, password });
        jwt = data.token;
        
        // Get user info
        const userData = await apiCall('me', 'GET');
        userId = userData.id;
        
        // Save to localStorage
        localStorage.setItem('jwt', jwt);
        localStorage.setItem('userId', userId);
        localStorage.setItem('userName', userData.name);
        
        updateUI();
    } catch (error) {
        alert('Login failed: ' + error.message);
        console.error('Login error:', error);
    }
};

// Register handler
registerBtn.onclick = async () => {
    const name = document.getElementById('registerName').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;

    try {
        const data = await apiCall('register', 'POST', { name, email, password });
        jwt = data.token;
        userId = data.user.id;
        
        // Save to localStorage
        localStorage.setItem('jwt', jwt);
        localStorage.setItem('userId', userId);
        localStorage.setItem('userName', data.user.name);
        
        updateUI();
    } catch (error) {
        alert('Registration failed: ' + error.message);
        console.error('Register error:', error);
    }
};

// Logout handler
logoutBtn.onclick = () => {
    localStorage.removeItem('jwt');
    localStorage.removeItem('userId');
    jwt = null;
    userId = null;
    updateUI();
};

// Fetch user info by ID
async function fetchUserInfo(userId) {
    try {
        const userData = await apiCall(`users/${userId}`, 'GET');
        return userData;
    } catch (error) {
        console.error('Error fetching user info:', error);
        return { name: "Unknown User" };
    }
}

// Update remote user's name in the UI
function updateRemoteUserName(name) {
    remotePeerName = name || "Remote User";
    
    // Update in both places - the video label and the placeholder
    const remoteLabel = document.querySelector('.video-wrapper:nth-child(2) .video-label');
    if (remoteLabel) {
        remoteLabel.textContent = remotePeerName;
    }
    
    // Update placeholder name
    document.getElementById('remoteUserName').textContent = remotePeerName;
}

// Send signal function (missing implementation)
async function sendSignal(data) {
    if (!jwt || !userId) {
        alert('You must be logged in to make calls');
        return;
    }
    
    try {
        // If 'to' is specified in the data, use it; otherwise use targetPeerId
        const to = data.to || targetPeerId;
        
        // Remove 'to' from the data if it exists, since we're passing it separately
        if (data.to) {
            const { to: _, ...dataWithoutTo } = data;
            data = dataWithoutTo;
        }
        
        await apiCall('signal/send', 'POST', { to, data });
    } catch (error) {
        console.error('Error sending signal:', error);
        throw new Error('Failed to send signal: ' + error.message);
    }
}

// Call functionality
startCallBtn.onclick = async () => {
    targetPeerId = peerIdInput.value;
    if (!targetPeerId) return alert("Enter peer ID");

    // Check if encryption is ready (unless 'none' is selected)
    if (currentEncryptionMethod !== 'none' && !isEncryptionReady) {
        alert("Encryption is not ready. Please refresh encryption keys.");
        return;
    }

    try {
        // Show connecting status
        setCallState("connecting");
        
        // Get remote user's name before starting the call
        const remoteUser = await fetchUserInfo(targetPeerId);
        updateRemoteUserName(remoteUser.name);
        
        // Initialize encryption key exchange
        await initializeKeyExchange(targetPeerId);
        
        // Continue with call setup
        await setupLocalMedia();
        await createPeerConnection();
        
        // Create the data channel before creating offer
        console.log("Creating data channel (caller)");
        dataChannel = peerConnection.createDataChannel("chat");
        setupDataChannel();
        
        showCallControls(true);
        isInCall = true;
        
        // Create and send offer
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        
        // Change status to "ringing" now that we're waiting for answer
        setCallState("ringing");
        
        // Send call offer signal
        sendSignal({ 
            type: 'call-offer',
            sdp: offer,
            callerName: localStorage.getItem('userName'), // Send our name to the callee
            encryptionMethod: currentEncryptionMethod // Tell callee what encryption we're using
        });
    } catch (error) {
        console.error('Error starting call:', error);
        alert('Failed to start call: ' + error.message);
        setCallState("idle");
    }
};

// Incoming call handlers
acceptCallBtn.onclick = async () => {
    try {
        incomingCallScreen.style.display = 'none';
        
        // Show connecting status when accepting a call
        setCallState("connecting");
        
        // Get remote user's name
        if (pendingOffer && pendingOffer.from) {
            const remoteUser = await fetchUserInfo(pendingOffer.from);
            updateRemoteUserName(remoteUser.name);
            
            // Get encryption method from offer if available
            if (pendingOffer.encryptionMethod) {
                // Set our encryption method to match the caller's
                currentEncryptionMethod = pendingOffer.encryptionMethod;
                encryptionMethodSelect.value = currentEncryptionMethod;
                await generateEncryptionKeys();
            }
            
            // Initialize encryption key exchange
            await initializeKeyExchange(pendingOffer.from);
        }
        
        await setupLocalMedia();
        await createPeerConnection();
        
        // For the receiver, we listen for the data channel
        peerConnection.ondatachannel = (event) => {
            console.log("Received data channel (receiver)");
            dataChannel = event.channel;
            setupDataChannel();
        };
        
        showCallControls(true);
        isInCall = true;
        
        // Set the remote description from the pending offer
        await peerConnection.setRemoteDescription(new RTCSessionDescription(pendingOffer.sdp));
        
        // Create and send answer
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        
        // Send call answer signal
        sendSignal({ 
            type: 'call-answer',
            sdp: answer,
            to: pendingOffer.from
        });
        
        targetPeerId = pendingOffer.from;
        hasPendingOffer = false;
        pendingOffer = null;
        
    } catch (error) {
        console.error('Error accepting call:', error);
        alert('Failed to accept call: ' + error.message);
        setCallState("idle");
    }
};

// Call Ended Overlay handlers
callAgainBtn.onclick = () => {
    callEndedOverlay.style.display = 'none';
    // If we have the targetPeerId saved, populate the input field
    if (targetPeerId) {
        peerIdInput.value = targetPeerId;
        // Automatically start the call again
        startCallBtn.click();
    }
};

closeCallEndedBtn.onclick = () => {
    callEndedOverlay.style.display = 'none';
    
    // Reset target peer ID
    targetPeerId = null;
    peerIdInput.value = '';
    
    // Reset remote name
    remotePeerName = "Remote User";
    updateRemoteUserName(remotePeerName);
    
    // Reset UI elements
    toggleVideoPlaceholder(true);
    showCallControls(false);
    showMessageSystem(false);
    
    // Make sure call state is set to idle
    setCallState("idle");
    
    // Stop and release local camera access
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
        localVideo.srcObject = null;
    }
};

// Call control handlers
muteAudioBtn.onclick = () => {
    if (localStream) {
        const audioTracks = localStream.getAudioTracks();
        if (audioTracks.length > 0) {
            const isEnabled = !audioTracks[0].enabled;
            audioTracks[0].enabled = isEnabled;
            muteAudioBtn.textContent = isEnabled ? 'Mute Audio' : 'Unmute Audio';
        }
    }
};

muteVideoBtn.onclick = () => {
    if (localStream) {
        const videoTracks = localStream.getVideoTracks();
        if (videoTracks.length > 0) {
            const isEnabled = !videoTracks[0].enabled;
            videoTracks[0].enabled = isEnabled;
            muteVideoBtn.textContent = isEnabled ? 'Mute Video' : 'Unmute Video';
        }
    }
};

endCallBtn.onclick = () => {
    const savedPeerName = remotePeerName;
    
    endCall();
    sendSignal({ type: 'call-ended', to: targetPeerId });
    
    // Show the call ended overlay with the remote user's name
    endedCallName.textContent = savedPeerName;
    callEndedOverlay.style.display = 'flex';
};

// Messaging handlers
sendMessageBtn.onclick = () => {
    const message = messageInput.value.trim();
    if (!message) return;
    
    if (!dataChannel || dataChannel.readyState !== 'open') {
        console.log("Data channel not available:", dataChannel);
        alert("Chat is not available yet. Wait for the connection to establish.");
        return;
    }
    
    sendMessage(message);
    messageInput.value = '';
};

messageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessageBtn.click();
    }
});

// Helper functions
async function setupLocalMedia() {
    try {
        if (!localStream) {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            localVideo.srcObject = localStream;
        }
    } catch (error) {
        console.error('Error accessing media devices:', error);
        throw new Error('Failed to access camera and microphone. Please check permissions.');
    }
}

// Create peer connection
async function createPeerConnection() {
    try {
        peerConnection = new RTCPeerConnection(servers);

        // Add local tracks to peer connection
        if (localStream) {
            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });
        }

        // Handle remote tracks
        peerConnection.ontrack = event => {
            console.log("Received remote track", event);
            remoteVideo.srcObject = event.streams[0];
            
            // Hide placeholder when video tracks are received
            // But wait a moment to ensure the video is properly loaded
            setTimeout(() => {
                if (event.streams[0].getVideoTracks().length > 0) {
                    toggleVideoPlaceholder(false);
                }
            }, 1000);
        };

        // Handle ICE candidates
        peerConnection.onicecandidate = event => {
            if (event.candidate) {
                sendSignal({ 
                    type: 'ice-candidate',
                    candidate: event.candidate,
                    to: targetPeerId
                });
            }
        };
        
        // Connection state change handler
        peerConnection.onconnectionstatechange = () => {
            console.log("Connection state change:", peerConnection.connectionState);
            if (peerConnection.connectionState === 'connected') {
                console.log("Peer connection established!");
                setCallState("connected");
            } else if (peerConnection.connectionState === 'failed' || 
                       peerConnection.connectionState === 'disconnected' || 
                       peerConnection.connectionState === 'closed') {
                console.log("Peer connection ended:", peerConnection.connectionState);
                
                // Only show the ended overlay if we were previously connected
                if (callState === "connected") {
                    endCall();
                    // Show call ended overlay
                    endedCallName.textContent = remotePeerName;
                    callEndedOverlay.style.display = 'flex';
                }
            }
        };

        // Connection state handling
        peerConnection.oniceconnectionstatechange = () => {
            console.log('ICE connection state:', peerConnection.iceConnectionState);
            if (peerConnection.iceConnectionState === 'connected' || 
                peerConnection.iceConnectionState === 'completed') {
                console.log("ICE connection established!");
                setCallState("connected");
                // Show message system when ICE connected (for both caller and receiver)
                setTimeout(() => {
                    showMessageSystem(true);
                }, 1000);
            }
            
            if (peerConnection.iceConnectionState === 'disconnected' || 
                peerConnection.iceConnectionState === 'failed' ||
                peerConnection.iceConnectionState === 'closed') {
                if (callState === "connected") {
                    const savedPeerName = remotePeerName;
                    endCall();
                    // Show call ended overlay
                    endedCallName.textContent = savedPeerName;
                    callEndedOverlay.style.display = 'flex';
                } else {
                    setCallState("idle");
                }
            }
        };

        return peerConnection;
    } catch (error) {
        console.error('Error creating peer connection:', error);
        throw new Error('Failed to create peer connection.');
    }
}

function setupDataChannel() {
    if (!dataChannel) {
        console.error("Data channel is null in setupDataChannel");
        return;
    }

    dataChannel.onopen = () => {
        console.log('Data channel opened! ReadyState:', dataChannel.readyState);
        isDataChannelOpen = true;
        showMessageSystem(true);
        
        // Send a test message when the channel opens
        setTimeout(() => {
            try {
                if (dataChannel && dataChannel.readyState === 'open') {
                    console.log("Sending test message on data channel");
                    dataChannel.send("Chat connected!");
                }
            } catch (e) {
                console.error("Error sending test message:", e);
            }
        }, 1000);
    };

    dataChannel.onclose = () => {
        console.log('Data channel closed');
        isDataChannelOpen = false;
        showMessageSystem(false);
    };

    dataChannel.onerror = (error) => {
        console.error("Data Channel Error:", error);
    };

    dataChannel.onmessage = (event) => {
        console.log("Encrypted message received:", event.data);
        // Decrypt the message
        const decryptedMessage = decryptMessage(event.data);
        addMessageToUI(decryptedMessage, 'received');
    };
}

async function sendMessage(message) {
    if (dataChannel && dataChannel.readyState === 'open') {
        console.log("Sending message:", message);
        
        try {
            // Encrypt the message before sending
            const encryptedMessage = await encryptMessage(message);
            dataChannel.send(encryptedMessage);
            // Still show the original message in UI
            addMessageToUI(message, 'sent');
        } catch (error) {
            console.error("Error encrypting message:", error);
            addSystemMessage("Failed to encrypt message");
        }
    } else {
        console.error("Cannot send message, data channel not open:", dataChannel?.readyState);
    }
}

function addMessageToUI(message, type) {
    const messageElement = document.createElement('div');
    messageElement.classList.add('message', type === 'sent' ? 'sent-message' : 'received-message');
    messageElement.textContent = message;
    messageContainer.appendChild(messageElement);
    messageContainer.scrollTop = messageContainer.scrollHeight;
}

function showCallControls(show) {
    callControls.style.display = show ? 'flex' : 'none';
}

function showMessageSystem(show) {
    messageSystem.style.display = show ? 'block' : 'none';
    if (show) {
        // Clear any previous messages when showing the system
        messageContainer.innerHTML = '';
        // Add a system message
        const systemMsg = document.createElement('div');
        systemMsg.classList.add('message', 'system-message');
        systemMsg.textContent = "Chat connected. You can now send messages.";
        messageContainer.appendChild(systemMsg);
    }
}

// End call - update to restore the placeholder
function endCall() {
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    
    if (dataChannel) {
        dataChannel.close();
        dataChannel = null;
    }
    
    isDataChannelOpen = false;
    
    if (remoteVideo.srcObject) {
        remoteVideo.srcObject.getTracks().forEach(track => track.stop());
        remoteVideo.srcObject = null;
    }
    
    // Show the placeholder again after call ends
    toggleVideoPlaceholder(true);
    
    showCallControls(false);
    showMessageSystem(false);
    isInCall = false;
    setCallState("ended");
}

// Set call state helper function
function setCallState(state) {
    callState = state;
    
    // Update UI based on call state
    switch(state) {
        case "idle":
            callStatusOverlay.style.display = 'none';
            localStatus.textContent = '';
            remoteStatus.textContent = '';
            break;
            
        case "connecting":
            callStatusText.textContent = "Connecting...";
            callStatusOverlay.style.display = 'flex';
            localStatus.textContent = 'Connecting';
            break;
            
        case "ringing":
            callStatusText.textContent = "Ringing...";
            callStatusOverlay.style.display = 'flex';
            localStatus.textContent = 'Ringing';
            break;
            
        case "connected":
            callStatusOverlay.style.display = 'none';
            localStatus.textContent = 'Connected';
            remoteStatus.textContent = 'Connected';
            break;
            
        case "ended":
            callStatusOverlay.style.display = 'none';
            break;
    }
}

// Add user ID and name display on the call UI
function addUserIdDisplay() {
    if (userId) {
        // Remove existing display if it exists
        const existing = document.getElementById('userIdDisplay');
        if (existing) {
            existing.remove();
        }
        
        const userIdDisplay = document.createElement('div');
        userIdDisplay.id = 'userIdDisplay';
        
        // Ensure we always have the user's name by fetching it if not in localStorage
        const userName = localStorage.getItem('userName');
        if (!userName) {
            // If userName is not in localStorage, fetch it immediately
            apiCall('me', 'GET')
                .then(userData => {
                    if (userData && userData.name) {
                        localStorage.setItem('userName', userData.name);
                        updateUserIdDisplay(userData.name);
                    }
                })
                .catch(error => console.error('Error fetching user info:', error));
        } else {
            updateUserIdDisplay(userName);
        }
        
        function updateUserIdDisplay(name) {
            userIdDisplay.innerHTML = `
                <div class="welcome-message">
                    <h3>Welcome ${name || 'User'}!</h3>
                    <p>Your ID is <strong>${userId}</strong>. Share this ID with others to receive calls.</p>
                </div>
            `;
        }
        
        // Initial display even if we're still fetching the name
        updateUserIdDisplay(userName);
        
        const callUiDiv = document.getElementById('callUI');
        callUiDiv.insertBefore(userIdDisplay, callUiDiv.firstChild);
    }
}

// Store user name after login/register
async function storeUserInfo() {
    try {
        const userData = await apiCall('me', 'GET');
        if (userData && userData.name) {
            localStorage.setItem('userName', userData.name);
        }
    } catch (error) {
        console.error('Error storing user info:', error);
    }
}

// Start the app
updateUI();
// If we're already logged in, fetch and store user info
if (jwt && userId) {
    storeUserInfo();
}
// Start polling for signals if logged in
setInterval(pollSignals, pollingInterval);

// Update signal handler for incoming calls
async function pollSignals() {
    if (!jwt || !userId) return;
    
    try {
        const messages = await apiCall('signal/receive', 'GET');

        for (const msg of messages) {
            const { from, data } = msg;
            
            // Handle different signal types
            switch (data.type) {
                case 'call-offer':
                    // Only handle the offer if we're not in a call
                    if (!isInCall) {
                        hasPendingOffer = true;
                        pendingOffer = { ...data, from };
                        
                        // Get caller's name
                        try {
                            const callerInfo = await fetchUserInfo(from);
                            callerIDElement.textContent = callerInfo.name || from;
                        } catch (error) {
                            callerIDElement.textContent = from;
                        }
                        
                        incomingCallScreen.style.display = 'flex';
                    } else {
                        // We're busy, send busy response
                        sendSignal({
                            type: 'call-busy',
                            to: from
                        });
                    }
                    break;
                    
                case 'call-answer':
                    if (isInCall && peerConnection) {
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp));
                        // Don't need to show ringing anymore after call is answered
                        setCallState("connecting"); // Will switch to "connected" when ICE is complete
                    }
                    break;
                    
                case 'call-rejected':
                    setCallState("idle");
                    alert('Call was rejected');
                    endCall();
                    break;
                    
                case 'call-busy':
                    setCallState("idle");
                    alert('User is busy in another call');
                    endCall();
                    break;
                    
                case 'call-ended':
                    if (isInCall) {
                        const savedPeerName = remotePeerName;
                        endCall();
                        // Show call ended overlay with remote user's name
                        endedCallName.textContent = savedPeerName;
                        callEndedOverlay.style.display = 'flex';
                    }
                    break;
                    
                case 'ice-candidate':
                    if (isInCall && peerConnection) {
                        try {
                            await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
                        } catch (e) {
                            console.error('Error adding ICE candidate:', e);
                        }
                    }
                    break;
                
                case 'key-exchange':
                case 'encrypted-key':
                    // Handle encryption key exchange
                    await handleKeyExchange(data, from);
                    break;
            }
        }
    } catch (error) {
        console.error('Error polling signals:', error);
    }
}

// Encryption initialization
async function initializeEncryption() {
    currentEncryptionMethod = encryptionMethodSelect.value;
    updateEncryptionUI();
    await generateEncryptionKeys();
    
    // Listen for encryption method changes
    encryptionMethodSelect.addEventListener('change', async function() {
        currentEncryptionMethod = this.value;
        updateEncryptionUI();
        await generateEncryptionKeys();
    });
    
    // Listen for refresh encryption button
    refreshEncryptionBtn.addEventListener('click', async function() {
        this.classList.add('refreshing');
        await generateEncryptionKeys();
        setTimeout(() => this.classList.remove('refreshing'), 1000);
    });
}

// Generate encryption keys based on selected method
async function generateEncryptionKeys() {
    if (!jwt || !userId) return;
    
    isEncryptionReady = false;
    updateEncryptionUI();
    
    try {
        const response = await apiCall('encryption/generate', 'POST', {
            method: currentEncryptionMethod
        });
        
        if (response.success) {
            switch(currentEncryptionMethod) {
                case 'aes':
                    encryptionKeys.aes = response.key;
                    break;
                case 'rsa':
                    encryptionKeys.publicKey = response.public_key;
                    // Private key is stored server-side
                    break;
                case 'none':
                    // No keys needed
                    break;
            }
            
            isEncryptionReady = true;
            updateEncryptionUI();
        } else {
            console.error('Failed to generate encryption keys:', response.error);
            showEncryptionError();
        }
    } catch (error) {
        console.error('Error generating encryption keys:', error);
        showEncryptionError();
    }
}

// Update encryption UI based on current state
function updateEncryptionUI() {
    // Update status indicator
    const isSecure = currentEncryptionMethod !== 'none' && isEncryptionReady;
    
    if (isSecure) {
        encryptionStatus.className = 'status-indicator secure';
        encryptionStatus.innerHTML = '<i class="fas fa-lock"></i>';
    } else if (currentEncryptionMethod === 'none') {
        encryptionStatus.className = 'status-indicator insecure';
        encryptionStatus.innerHTML = '<i class="fas fa-unlock"></i> Unencrypted';
    } else {
        encryptionStatus.className = 'status-indicator insecure';
        encryptionStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Not Ready';
    }
}

// Show encryption error in UI
function showEncryptionError() {
    encryptionStatus.className = 'status-indicator insecure';
    encryptionStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
    isEncryptionReady = false;
}

// Initialize key exchange with peer during call setup
async function initializeKeyExchange(peerId) {
    if (currentEncryptionMethod === 'none') {
        // No encryption, nothing to do
        return true;
    }
    
    try {
        if (currentEncryptionMethod === 'rsa') {
            // For RSA, we exchange public keys
            // Send signal with our public key to peer
            await sendSignal({
                type: 'key-exchange',
                method: 'rsa',
                publicKey: encryptionKeys.publicKey,
                to: peerId
            });
            
            // Wait for peer's public key in signal handler
            // The rest will happen in handleKeyExchange
            
        } else if (currentEncryptionMethod === 'aes') {
            // For AES, caller sends encrypted AES key to receiver
            // This happens after we get peer's public key in signal handler
            // The rest will happen in handleKeyExchange
        }
        
        return true;
    } catch (error) {
        console.error('Key exchange failed:', error);
        return false;
    }
}

// Handle key exchange signals
async function handleKeyExchange(data, fromPeerId) {
    try {
        if (data.type === 'key-exchange') {
            if (data.method === 'rsa') {
                // Store peer's public key
                encryptionKeys.peerPublicKey = data.publicKey;
                
                // Respond with our public key if we're receiving this
                if (!isInCall) {
                    await sendSignal({
                        type: 'key-exchange',
                        method: 'rsa',
                        publicKey: encryptionKeys.publicKey,
                        to: fromPeerId
                    });
                }
                
                // If we're the caller and using AES method (hybrid encryption),
                // we need to encrypt our AES key with peer's RSA public key
                if (isInCall && currentEncryptionMethod === 'aes' && encryptionKeys.aes) {
                    // Use API to encrypt AES key with peer's public key
                    const response = await apiCall('encryption/exchange', 'POST', {
                        peer_public_key: encryptionKeys.peerPublicKey,
                        method: 'aes'
                    });
                    
                    if (response.success) {
                        // Send the encrypted AES key to peer
                        await sendSignal({
                            type: 'encrypted-key',
                            method: 'aes',
                            encryptedKey: response.encrypted_key,
                            to: fromPeerId
                        });
                    }
                }
            }
        } else if (data.type === 'encrypted-key') {
            // Recipient receives encrypted AES key
            if (data.method === 'aes') {
                // Store the encrypted key using the API
                await apiCall('encryption/store-aes-key', 'POST', {
                    encrypted_key: data.encryptedKey
                });
            }
        }
    } catch (error) {
        console.error('Error handling key exchange:', error);
    }
}

// Encrypt message before sending
async function encryptMessage(message) {
    if (currentEncryptionMethod === 'none') {
        return message;
    }
    
    // For demo purposes, we'll just add a prefix for proof of encryption
    // In a real app, you'd send the encrypted message to the backend for encryption
    // or use a JavaScript encryption library like CryptoJS
    return `[ENCRYPTED] ${message}`;
}

// Decrypt message after receiving
function decryptMessage(encryptedMessage) {
    if (currentEncryptionMethod === 'none' || !encryptedMessage.startsWith('[ENCRYPTED]')) {
        return encryptedMessage;
    }
    
    // For demo purposes, we'll just remove the prefix
    // In a real app, you'd send the encrypted message to the backend for decryption
    return encryptedMessage.replace('[ENCRYPTED] ', '');
}

// Helper function to add system messages
function addSystemMessage(message) {
    const systemMsg = document.createElement('div');
    systemMsg.classList.add('message', 'system-message');
    systemMsg.textContent = message;
    messageContainer.appendChild(systemMsg);
    messageContainer.scrollTop = messageContainer.scrollHeight;
}

// DOM elements for video placeholder
const remoteVideoPlaceholder = document.getElementById('remoteVideoPlaceholder');
const remoteVideoElement = document.getElementById('remoteVideo');

// Helper function to show/hide video placeholder
function toggleVideoPlaceholder(show) {
    if (show) {
        remoteVideoPlaceholder.style.display = 'flex';
    } else {
        remoteVideoPlaceholder.style.display = 'none';
    }
}

// Show placeholder by default
toggleVideoPlaceholder(true);
