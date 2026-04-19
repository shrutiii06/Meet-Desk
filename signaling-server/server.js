/**
 * MeetDesk WebRTC Signaling Server
 * Handles WebSocket connections for peer-to-peer video conferencing
 */

const WebSocket = require('ws');
const { v4: uuidv4 } = require('uuid');

const PORT = process.env.PORT || 8080;
const wss = new WebSocket.Server({ port: PORT });

// Store active rooms and their participants
const rooms = new Map();

console.log(`🚀 MeetDesk Signaling Server running on port ${PORT}`);

wss.on('connection', (ws) => {
    let currentRoom = null;
    let currentUserId = null;
    let currentUserName = null;

    console.log('📱 New WebSocket connection');

    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            
            switch (data.type) {
                case 'join':
                    handleJoin(ws, data);
                    break;
                
                case 'offer':
                    handleOffer(ws, data);
                    break;
                
                case 'answer':
                    handleAnswer(ws, data);
                    break;
                
                case 'ice-candidate':
                    handleIceCandidate(ws, data);
                    break;
                
                case 'chat':
                    handleChat(ws, data);
                    break;
                
                case 'leave':
                    handleLeave(ws);
                    break;
                
                case 'toggle-audio':
                case 'toggle-video':
                    handleMediaToggle(ws, data);
                    break;
                
                default:
                    console.log('Unknown message type:', data.type);
            }
        } catch (error) {
            console.error('Error parsing message:', error);
        }
    });

    ws.on('close', () => {
        console.log('📴 WebSocket connection closed');
        handleLeave(ws);
    });

    ws.on('error', (error) => {
        console.error('WebSocket error:', error);
    });

    // Handle user joining a room
    function handleJoin(ws, data) {
        const { roomId, userId, userName } = data;
        
        currentRoom = roomId;
        currentUserId = userId;
        currentUserName = userName;

        // Create room if it doesn't exist
        if (!rooms.has(roomId)) {
            rooms.set(roomId, new Map());
            console.log(`🏠 Created new room: ${roomId}`);
        }

        const room = rooms.get(roomId);
        
        // Add user to room
        room.set(userId, {
            ws,
            userId,
            userName,
            audioEnabled: true,
            videoEnabled: true
        });

        console.log(`👤 User ${userName} (${userId}) joined room ${roomId}`);
        console.log(`   Room now has ${room.size} participant(s)`);

        // Send current participants list to the new user
        const participants = Array.from(room.values())
            .filter(p => p.userId !== userId)
            .map(p => ({
                userId: p.userId,
                userName: p.userName,
                audioEnabled: p.audioEnabled,
                videoEnabled: p.videoEnabled
            }));

        ws.send(JSON.stringify({
            type: 'participants',
            participants
        }));

        // Notify other participants about the new user
        broadcastToRoom(roomId, {
            type: 'user-joined',
            userId,
            userName,
            audioEnabled: true,
            videoEnabled: true
        }, userId);
    }

    // Handle WebRTC offer
    function handleOffer(ws, data) {
        const { targetUserId, offer } = data;
        
        if (!currentRoom) return;
        
        const room = rooms.get(currentRoom);
        const targetUser = room.get(targetUserId);
        
        if (targetUser) {
            targetUser.ws.send(JSON.stringify({
                type: 'offer',
                fromUserId: currentUserId,
                fromUserName: currentUserName,
                offer
            }));
            
            console.log(`📞 Offer sent from ${currentUserName} to ${targetUser.userName}`);
        }
    }

    // Handle WebRTC answer
    function handleAnswer(ws, data) {
        const { targetUserId, answer } = data;
        
        if (!currentRoom) return;
        
        const room = rooms.get(currentRoom);
        const targetUser = room.get(targetUserId);
        
        if (targetUser) {
            targetUser.ws.send(JSON.stringify({
                type: 'answer',
                fromUserId: currentUserId,
                answer
            }));
            
            console.log(`✅ Answer sent from ${currentUserName} to ${targetUser.userName}`);
        }
    }

    // Handle ICE candidate
    function handleIceCandidate(ws, data) {
        const { targetUserId, candidate } = data;
        
        if (!currentRoom) return;
        
        const room = rooms.get(currentRoom);
        const targetUser = room.get(targetUserId);
        
        if (targetUser) {
            targetUser.ws.send(JSON.stringify({
                type: 'ice-candidate',
                fromUserId: currentUserId,
                candidate
            }));
        }
    }

    // Handle chat message
    function handleChat(ws, data) {
        const { message } = data;
        
        if (!currentRoom) return;
        
        console.log(`💬 Chat from ${currentUserName}: ${message}`);
        
        broadcastToRoom(currentRoom, {
            type: 'chat',
            userId: currentUserId,
            userName: currentUserName,
            message,
            timestamp: new Date().toISOString()
        }, currentUserId);
    }

    // Handle media toggle (audio/video)
    function handleMediaToggle(ws, data) {
        if (!currentRoom) return;
        
        const room = rooms.get(currentRoom);
        const user = room.get(currentUserId);
        
        if (user) {
            if (data.type === 'toggle-audio') {
                user.audioEnabled = data.enabled;
            } else if (data.type === 'toggle-video') {
                user.videoEnabled = data.enabled;
            }
            
            // Notify other participants
            broadcastToRoom(currentRoom, {
                type: 'media-toggle',
                userId: currentUserId,
                mediaType: data.type === 'toggle-audio' ? 'audio' : 'video',
                enabled: data.enabled
            }, currentUserId);
        }
    }

    // Handle user leaving
    function handleLeave(ws) {
        if (!currentRoom || !currentUserId) return;
        
        const room = rooms.get(currentRoom);
        
        if (room) {
            room.delete(currentUserId);
            
            console.log(`👋 User ${currentUserName} left room ${currentRoom}`);
            console.log(`   Room now has ${room.size} participant(s)`);
            
            // Notify other participants
            broadcastToRoom(currentRoom, {
                type: 'user-left',
                userId: currentUserId,
                userName: currentUserName
            });
            
            // Delete room if empty
            if (room.size === 0) {
                rooms.delete(currentRoom);
                console.log(`🗑️  Deleted empty room: ${currentRoom}`);
            }
        }
        
        currentRoom = null;
        currentUserId = null;
        currentUserName = null;
    }

    // Broadcast message to all users in a room (except sender)
    function broadcastToRoom(roomId, message, excludeUserId = null) {
        const room = rooms.get(roomId);
        
        if (room) {
            room.forEach((user, userId) => {
                if (userId !== excludeUserId && user.ws.readyState === WebSocket.OPEN) {
                    user.ws.send(JSON.stringify(message));
                }
            });
        }
    }
});

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('SIGTERM signal received: closing WebSocket server');
    wss.close(() => {
        console.log('WebSocket server closed');
        process.exit(0);
    });
});

process.on('SIGINT', () => {
    console.log('\nSIGINT signal received: closing WebSocket server');
    wss.close(() => {
        console.log('WebSocket server closed');
        process.exit(0);
    });
});
