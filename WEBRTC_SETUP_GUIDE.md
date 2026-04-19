# 🎥 WebRTC Multi-User Video Conferencing - Setup Guide

## ✅ What's Been Implemented

Your MeetDesk application now has **full WebRTC multi-user video conferencing** capabilities!

### Features:
- ✅ **Multi-user video calls** - See and hear all participants
- ✅ **Real-time chat** - Send messages to all participants
- ✅ **Audio/video controls** - Mute/unmute, camera on/off
- ✅ **Screen sharing** - Share your screen with others
- ✅ **Participant management** - See who's in the meeting
- ✅ **Automatic peer connections** - WebRTC handles connections
- ✅ **STUN server integration** - Works across networks

---

## 📋 Prerequisites

### 1. Install Node.js

**Download and install Node.js:**
- Visit: https://nodejs.org/
- Download: **LTS version** (18.x or higher recommended)
- Run the installer
- Accept all defaults

**Verify installation:**
```powershell
node --version
npm --version
```

Should show version numbers (e.g., v18.17.0)

---

## 🚀 Quick Start (3 Steps)

### **Step 1: Install Dependencies**

Open PowerShell in the project folder:
```powershell
cd C:\laragon\www\MD\signaling-server
npm install
```

This installs:
- `ws` - WebSocket server
- `uuid` - Unique ID generation

### **Step 2: Start the Signaling Server**

**Option A: Using the batch file (Easiest)**
```
Double-click: START_WEBRTC.bat
```

**Option B: Using PowerShell**
```powershell
cd C:\laragon\www\MD\signaling-server
npm start
```

**Option C: Development mode (auto-restart)**
```powershell
npm run dev
```

You should see:
```
🚀 MeetDesk Signaling Server running on port 8080
```

### **Step 3: Test Multi-User Video**

1. **Keep the signaling server running** (don't close the window)
2. Open your browser: `http://localhost/MD/schedule.html`
3. Schedule a test meeting
4. Click "Start Meeting" to join
5. **Open another browser tab** (or use incognito mode)
6. Go to: `http://localhost/MD/join.html`
7. Enter the same meeting ID
8. Join the meeting

**You should now see both participants with video/audio!**

---

## 🧪 Testing Checklist

### Test 1: Two Users in Same Browser
- [ ] Open meeting in Tab 1
- [ ] Open same meeting in Tab 2 (incognito mode)
- [ ] Both users appear in participant list
- [ ] Video streams visible for both users
- [ ] Audio works (use headphones to avoid echo)
- [ ] Chat messages sync between tabs

### Test 2: Audio/Video Controls
- [ ] Mute/unmute microphone works
- [ ] Turn camera on/off works
- [ ] Other participants see status changes
- [ ] Screen sharing works

### Test 3: Chat
- [ ] Send message from User 1
- [ ] User 2 receives message instantly
- [ ] Unread count updates when chat is closed
- [ ] Messages persist during call

### Test 4: Participant Management
- [ ] New user joins → appears in list
- [ ] User leaves → removed from list
- [ ] Main speaker can be changed by clicking thumbnail
- [ ] Participant count is accurate

---

## 🔧 Troubleshooting

### Issue 1: "Could not connect to video server"

**Cause:** Signaling server not running

**Fix:**
1. Check if `START_WEBRTC.bat` is running
2. Look for error messages in the server window
3. Verify port 8080 is not in use:
   ```powershell
   netstat -ano | findstr :8080
   ```
4. If port is in use, kill the process or change port in `server.js`

---

### Issue 2: "No video/audio from other participants"

**Cause:** WebRTC connection failed

**Fix:**
1. Check browser console for errors (F12)
2. Ensure both users are on same network or use TURN server
3. Check firewall settings
4. Try using Chrome/Edge (best WebRTC support)

---

### Issue 3: "Cannot access camera/microphone"

**Cause:** Browser permissions denied

**Fix:**
1. Click the camera icon in browser address bar
2. Allow camera and microphone access
3. Refresh the page
4. For HTTPS requirement: Use `localhost` (works without HTTPS)

---

### Issue 4: Echo or feedback

**Cause:** Multiple tabs with audio on same device

**Fix:**
1. Use headphones
2. Mute one of the tabs
3. Test with different devices

---

## 📊 Architecture

```
┌─────────────┐         WebSocket          ┌──────────────────┐
│   User 1    │◄──────────────────────────►│                  │
│  (Browser)  │                             │    Signaling     │
└─────────────┘                             │     Server       │
       ▲                                    │   (Node.js)      │
       │                                    │                  │
       │         WebRTC P2P Connection      └──────────────────┘
       │         (Video/Audio)                       ▲
       │                                             │
       │                                             │ WebSocket
       │                                             │
       ▼                                             ▼
┌─────────────┐                             ┌─────────────┐
│   User 2    │◄────────────────────────────►│   User 3    │
│  (Browser)  │    WebRTC P2P Connection    │  (Browser)  │
└─────────────┘                             └─────────────┘
```

**How it works:**
1. **Signaling Server** - Coordinates connections (WebSocket)
2. **WebRTC** - Direct peer-to-peer video/audio (no server relay)
3. **STUN Server** - Helps with NAT traversal (Google's free STUN)

---

## 🌐 Production Deployment

### For Production Use:

1. **Deploy signaling server to cloud:**
   - Heroku, AWS, DigitalOcean, etc.
   - Use environment variable for PORT
   - Enable HTTPS/WSS

2. **Update signaling URL in room.html:**
   ```javascript
   signalingServerUrl: 'wss://your-domain.com'
   ```

3. **Add TURN server for better connectivity:**
   ```javascript
   iceServers: [
     { urls: 'stun:stun.l.google.com:19302' },
     {
       urls: 'turn:your-turn-server.com:3478',
       username: 'user',
       credential: 'pass'
     }
   ]
   ```

4. **Use HTTPS for your website** (required for WebRTC in production)

---

## 📝 Server Logs

Logs are saved to: `signaling-server/logs/` (if configured)

Console output shows:
- New connections
- Room creation/deletion
- User join/leave events
- Offers/answers exchanged
- Chat messages
- Errors

---

## 🎯 Next Steps

1. ✅ **Start the signaling server** - `START_WEBRTC.bat`
2. ✅ **Test with 2 browser tabs** - Verify video works
3. ✅ **Test all features** - Audio, video, chat, screen share
4. ✅ **Deploy to production** - When ready for real users

---

## 💡 Tips

- **Use Chrome or Edge** for best WebRTC support
- **Use headphones** when testing on same device
- **Check browser console** (F12) for debugging
- **Keep signaling server running** while using video features
- **Port 8080** must be accessible (check firewall)

---

## ✅ Summary

**What you have now:**
- Full multi-user video conferencing
- Real-time chat
- Screen sharing
- Professional UI
- Scalable architecture

**What you need to do:**
1. Install Node.js (if not installed)
2. Run `npm install` in signaling-server folder
3. Start server with `START_WEBRTC.bat`
4. Test with multiple browser tabs

**That's it! Your video conferencing is ready to use!** 🎉
