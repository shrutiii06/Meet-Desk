/**
 * WebRTC Client for MeetDesk
 * Handles both Peer-to-Peer (Mesh) connections for small groups
 * and highly scalable SFU Cloud (Agora) connections for massive webinars.
 */

class WebRTCClient {
    constructor(signalingServerUrl) {
        this.signalingServerUrl = signalingServerUrl;
        this.ws = null;
        this.roomId = null;
        this.userId = null;
        this.userName = null;
        this.localStream = null;
        
        // P2P State
        this.peerConnections = new Map();
        this.remoteStreams = new Map();
        
        // Webinar Cloud SFU State (Agora Integration)
        // -> To enable 1000+ person webinars, paste your Free Agora Web SDK App ID below.
        this.AGORA_APP_ID = ''; 
        this.agoraClient = null;
        this.isWebinarMode = this.AGORA_APP_ID.length > 5;

        // ICE servers (STUN/TURN)
        this.iceServers = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };
        
        // Event callbacks
        this.onParticipantJoined = null;
        this.onParticipantLeft = null;
        this.onRemoteStream = null;
        this.onChatMessage = null;
        this.onMediaToggle = null;
        this.onConnectionStateChange = null;
    }
    
    // Connect to signaling server (or Cloud SFU)
    async connect(roomId, userId, userName) {
        this.roomId = roomId;
        this.userId = userId;
        this.userName = userName;

        if (this.isWebinarMode) {
            console.log('🚀 Connecting via Webinar Cloud Mode (SFU) for Massive Scale...');
            await this.connectWebinarCloud();
        }

        // We ALWAYS connect to our local signaling server for Chat & Toggles (Even in Webinar mode)
        return new Promise((resolve, reject) => {
            try {
                this.ws = new WebSocket(this.signalingServerUrl);
                
                this.ws.onopen = () => {
                    console.log('✅ Connected to local signaling server (For Chat/P2P)');
                    this.send({ type: 'join', roomId: this.roomId, userId: this.userId, userName: this.userName });
                    resolve();
                };
                
                this.ws.onmessage = (event) => {
                    this.handleSignalingMessage(JSON.parse(event.data));
                };
                
                this.ws.onerror = (error) => {
                    console.error('❌ WebSocket error:', error);
                    reject(error);
                };
                
                this.ws.onclose = () => {
                    console.log('📴 Disconnected from signaling server');
                    this.cleanup();
                };
            } catch (error) {
                reject(error);
            }
        });
    }

    /* ----------------------------------------------------
       ☁️ CLOUD WEBINAR SFU LOGIC (SCALABLE TO 10,000+)
       ---------------------------------------------------- */
    async connectWebinarCloud() {
        if (typeof AgoraRTC === 'undefined') {
            console.error("Agora RTC SDK not loaded. Please inject the script in room.html.");
            return;
        }

        this.agoraClient = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

        this.agoraClient.on("user-published", async (user, mediaType) => {
            await this.agoraClient.subscribe(user, mediaType);
            console.log("📥 Subscribed to Cloud Stream from:", user.uid);

            if (mediaType === "video" || mediaType === "audio") {
                const stream = new MediaStream();
                if (user.audioTrack) stream.addTrack(user.audioTrack.getMediaStreamTrack());
                if (user.videoTrack) stream.addTrack(user.videoTrack.getMediaStreamTrack());
                
                if (this.onRemoteStream) {
                    this.onRemoteStream(user.uid, stream);
                }
            }
        });

        this.agoraClient.on("user-unpublished", (user) => {
            console.log("🛑 User stopped publishing:", user.uid);
        });

        try {
            // Generate a random numeric UID for Agora since it requires numbers sometimes
            const numericUid = Math.floor(Math.random() * 1000000);
            
            // Fetch Secure Token from Backend
            console.log("🔒 Requesting Secure Authentication Token...");
            const response = await fetch(`/MD/api/meetings/generate-token.php?roomId=${this.roomId}&uid=${numericUid}`);
            const data = await response.json();
            
            if (!response.ok || !data.success) {
                throw new Error(data.error || "Failed to generate token");
            }
            
            await this.agoraClient.join(data.appId, this.roomId, data.token, data.uid);
            console.log("✅ Joined Cloud Webinar Room Securely!");

            // We handle track assignment locally after they join.
        } catch (e) {
            console.error("Cloud Webinar Auth Failed - Falling back strictly to P2P Chat:", e);
        }
    }
    
    /* ----------------------------------------------------
       🤝 PEER-TO-PEER BACKUP LOGIC & SIGNALING
       ---------------------------------------------------- */
    async handleSignalingMessage(data) {
        switch (data.type) {
            case 'participants':
                for (const participant of data.participants) {
                    if (this.onParticipantJoined) this.onParticipantJoined(participant);
                    if (!this.isWebinarMode) await this.createOffer(participant.userId);
                }
                break;
            case 'user-joined':
                if (this.onParticipantJoined) this.onParticipantJoined(data);
                break;
            case 'user-left':
                this.removePeer(data.userId);
                if (this.onParticipantLeft) this.onParticipantLeft(data);
                break;
            case 'offer':
                if (!this.isWebinarMode) await this.handleOffer(data.fromUserId, data.fromUserName, data.offer);
                break;
            case 'answer':
                if (!this.isWebinarMode) await this.handleAnswer(data.fromUserId, data.answer);
                break;
            case 'ice-candidate':
                if (!this.isWebinarMode) await this.handleIceCandidate(data.fromUserId, data.candidate);
                break;
            case 'chat':
                if (this.onChatMessage) this.onChatMessage(data);
                break;
            case 'media-toggle':
                if (this.onMediaToggle) this.onMediaToggle(data);
                break;
        }
    }
    
    createPeerConnection(peerId) {
        const pc = new RTCPeerConnection(this.iceServers);
        
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => pc.addTrack(track, this.localStream));
        }
        
        pc.onicecandidate = (event) => {
            if (event.candidate) this.send({ type: 'ice-candidate', targetUserId: peerId, candidate: event.candidate });
        };
        
        pc.ontrack = (event) => {
            this.remoteStreams.set(peerId, event.streams[0]);
            if (this.onRemoteStream) this.onRemoteStream(peerId, event.streams[0]);
        };
        
        pc.onconnectionstatechange = () => {
            if (this.onConnectionStateChange) this.onConnectionStateChange(peerId, pc.connectionState);
            if (pc.connectionState === 'failed' || pc.connectionState === 'disconnected') this.removePeer(peerId);
        };
        
        pc.onnegotiationneeded = async () => {
            try {
                // Ensure we don't glare
                if (pc.signalingState !== "stable") return;
                const offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                this.send({ type: 'offer', targetUserId: peerId, offer: offer });
            } catch (err) {
                console.error("Renegotiation error:", err);
            }
        };
        
        this.peerConnections.set(peerId, pc);
        return pc;
    }
    
    async createOffer(peerId) {
        try {
            const pc = this.createPeerConnection(peerId);
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            this.send({ type: 'offer', targetUserId: peerId, offer: offer });
        } catch (error) { console.error('Error creating offer:', error); }
    }
    
    async handleOffer(peerId, peerName, offer) {
        try {
            let pc = this.peerConnections.get(peerId);
            if (!pc) {
                pc = this.createPeerConnection(peerId);
            }
            // If there's an ongoing negotiation state that conflicts, rollback if we need to.
            // But standard simple handling:
            if (pc.signalingState !== "stable") {
                await Promise.all([
                    pc.setLocalDescription({type: 'rollback'}),
                    pc.setRemoteDescription(new RTCSessionDescription(offer))
                ]);
            } else {
                await pc.setRemoteDescription(new RTCSessionDescription(offer));
            }
            
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            this.send({ type: 'answer', targetUserId: peerId, answer: answer });
        } catch (error) { console.error('Error handling offer:', error); }
    }
    
    async handleAnswer(peerId, answer) {
        try {
            const pc = this.peerConnections.get(peerId);
            if (pc) await pc.setRemoteDescription(new RTCSessionDescription(answer));
        } catch (error) { console.error('Error handling answer:', error); }
    }
    
    async handleIceCandidate(peerId, candidate) {
        try {
            const pc = this.peerConnections.get(peerId);
            if (pc) await pc.addIceCandidate(new RTCIceCandidate(candidate));
        } catch (error) { console.error('Error handling ICE:', error); }
    }
    
    // Set local media stream
    async setLocalStream(stream) {
        this.localStream = stream;

        // If Webinar Mode, push to Cloud Server!
        if (this.isWebinarMode && this.agoraClient) {
            try {
                // We create Agora compatible tracks
                let audioTrack = null;
                let videoTrack = null;
                if (stream.getAudioTracks().length > 0) {
                    audioTrack = AgoraRTC.createCustomAudioTrack({ mediaStreamTrack: stream.getAudioTracks()[0] });
                }
                if (stream.getVideoTracks().length > 0) {
                    videoTrack = AgoraRTC.createCustomVideoTrack({ mediaStreamTrack: stream.getVideoTracks()[0] });
                }
                const tracksToPublish = [];
                if (audioTrack) tracksToPublish.push(audioTrack);
                if (videoTrack) tracksToPublish.push(videoTrack);
                
                await this.agoraClient.publish(tracksToPublish);
                console.log("☁️ Local stream published to Webinar Cloud successfully.");
            } catch (e) {
                console.error("Failed publishing to Cloud SFU", e);
            }
        } 
        
        // P2P Logic
        this.peerConnections.forEach(async (pc, peerId) => {
            try {
                for (const track of stream.getTracks()) {
                    const sender = pc.getSenders().find(s => s.track && s.track.kind === track.kind);
                    if (sender) {
                        console.log(`P2P: Replacing ${track.kind} track for peer ${peerId}`);
                        await sender.replaceTrack(track);
                    } else {
                        console.log(`P2P: Adding new ${track.kind} track for peer ${peerId}`);
                        pc.addTrack(track, stream);
                    }
                }
            } catch (err) {
                console.error("Failed to replace track natively:", err);
            }
        });
    }
    
    sendChatMessage(message) {
        this.send({ type: 'chat', message: message });
    }
    
    toggleAudio(enabled) {
        if (this.localStream) this.localStream.getAudioTracks().forEach(track => track.enabled = enabled);
        this.send({ type: 'toggle-audio', enabled: enabled });
    }
    
    toggleVideo(enabled) {
        if (this.localStream) this.localStream.getVideoTracks().forEach(track => track.enabled = enabled);
        this.send({ type: 'toggle-video', enabled: enabled });
    }
    
    removePeer(peerId) {
        const pc = this.peerConnections.get(peerId);
        if (pc) { pc.close(); this.peerConnections.delete(peerId); }
        this.remoteStreams.delete(peerId);
    }
    
    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) this.ws.send(JSON.stringify(data));
    }
    
    async leave() {
        if (this.isWebinarMode && this.agoraClient) {
            await this.agoraClient.leave();
        }
        this.send({ type: 'leave' });
        this.cleanup();
    }
    
    cleanup() {
        this.peerConnections.forEach(pc => pc.close());
        this.peerConnections.clear();
        this.remoteStreams.clear();
        if (this.ws) { this.ws.close(); this.ws = null; }
    }
}

if (typeof module !== 'undefined' && module.exports) module.exports = WebRTCClient;
