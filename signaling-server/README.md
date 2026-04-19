# MeetDesk WebRTC Signaling Server

WebSocket-based signaling server for peer-to-peer video conferencing.

## Installation

1. Install Node.js (if not already installed):
   - Download from: https://nodejs.org/
   - Recommended version: 18.x or higher

2. Install dependencies:
   ```bash
   cd C:\laragon\www\MD\signaling-server
   npm install
   ```

## Running the Server

### Development Mode (with auto-restart):
```bash
npm run dev
```

### Production Mode:
```bash
npm start
```

The server will run on **port 8080** by default.

## Features

- ✅ WebSocket-based signaling
- ✅ Multi-room support
- ✅ Peer-to-peer WebRTC connections
- ✅ Real-time chat
- ✅ Audio/video toggle notifications
- ✅ Participant join/leave events
- ✅ ICE candidate exchange

## Message Types

### Client → Server:
- `join` - Join a room
- `offer` - Send WebRTC offer
- `answer` - Send WebRTC answer
- `ice-candidate` - Send ICE candidate
- `chat` - Send chat message
- `toggle-audio` - Toggle audio on/off
- `toggle-video` - Toggle video on/off
- `leave` - Leave room

### Server → Client:
- `participants` - List of current participants
- `user-joined` - New user joined
- `user-left` - User left
- `offer` - WebRTC offer from peer
- `answer` - WebRTC answer from peer
- `ice-candidate` - ICE candidate from peer
- `chat` - Chat message
- `media-toggle` - Peer toggled audio/video

## Configuration

Default port: **8080**

To change port, set environment variable:
```bash
PORT=3000 npm start
```

## Logs

The server logs all activities:
- New connections
- Room creation/deletion
- User join/leave
- Offers/answers exchanged
- Chat messages
- Errors

## Testing

1. Start the server: `npm start`
2. Open room.html in two different browser tabs
3. Join the same meeting in both tabs
4. Video/audio should connect automatically

## Troubleshooting

**Server won't start:**
- Check if port 8080 is already in use
- Run: `netstat -ano | findstr :8080`
- Kill the process or use a different port

**WebSocket connection fails:**
- Ensure server is running
- Check firewall settings
- Verify WebSocket URL in room.html matches server address

**No video/audio:**
- Check browser permissions for camera/microphone
- Ensure HTTPS or localhost (required for WebRTC)
- Check browser console for errors
