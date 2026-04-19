# MeetDesk Deployment Guide (Going Live for Free)

This guide walks you through exactly how to take your local Laragon project and put it on the real internet for everyone to use, without buying a domain name.

## Preparing the Code

Before moving files to the internet, make the following updates to your local code:

1. **Update the Database Connection:**
   - Open `api/database.php`
   - You will replace `mongodb://localhost:27017` with the Atlas URL (see Step 1).
2. **Update the Signaling Server Link:**
   - Open `room.html`
   - Find `ws://localhost:8080` (around line ~420).
   - You will replace this with your new Render URL (see Step 2).
3. **Update API URLs (If Hardcoded):**
   - Ensure anywhere that calls `http://localhost/MD/api/...` is updated to instead point to `https://your-free-subdomain.com/api/...`

---

## Step 1: Host the Database (MongoDB Atlas)

Currently, your user accounts are saved on your local hard drive.

1. Go to [MongoDB Atlas](https://www.mongodb.com/cloud/atlas/register) and create a free account.
2. Build a new "Free/Shared Cluster".
3. Under **Database Access**, create a user (e.g., username `admin`, password `securePassword123`).
4. Under **Network Access**, click "Add IP Address" -> click **"Allow Access From Anywhere"** (`0.0.0.0/0`).
5. Click **Connect** on your cluster, choose "Connect your application", and copy the **Connection String**.
   *(It looks like `mongodb+srv://admin:securePassword123@cluster0.abcde.mongodb.net/`)*
6. Paste this string into `api/database.php`.

---

## Step 2: Host the Video Signaling Server (Render)

WebRTC relies on WebSockets, which need a constantly running Node.js server. 

1. Create a free account on [GitHub](https://github.com/).
2. Create a new repository called `meetdesk-server`.
3. Upload ONLY the files from your `signaling-server` folder (`server.js`, `package.json`, `package-lock.json`) into this GitHub repository.
4. Create a free account on [Render.com](https://render.com/).
5. Click **New +** > **Web Service**.
6. Connect your GitHub account and select your `meetdesk-server` repository.
7. In the settings:
   - **Environment:** Node
   - **Build Command:** `npm install`
   - **Start Command:** `node server.js`
8. Click **Create Web Service**. 
9. After a few minutes, Render will give you a link (e.g., `https://meetdesk-video.onrender.com`).
10. Open your `room.html` code and change `ws://localhost:8080` to `wss://meetdesk-video.onrender.com` (notice the "wss" for secure websocket).

---

## Step 3: Host the PHP/HTML Website (InfinityFree)

Now you need to upload your frontend UI and your PHP backend.

1. Go to [InfinityFree](https://infinityfree.com/) and create an account.
2. Click **Create Account** and pick a free subdomain (e.g., `meetdesk.epizy.com`).
3. InfinityFree will provide you with **FTP Details** (Host, Username, Password).
4. Download and open [FileZilla](https://filezilla-project.org/).
5. Enter your FTP Details into FileZilla to connect to your live server.
6. On the right side of FileZilla, open the `htdocs` folder. Delete any default files in there.
7. On the left side of FileZilla, navigate to `C:\laragon\www\MD`.
8. Highlight everything inside the `MD` folder and drag it over to the right side (into `htdocs`).
9. Wait for the upload to complete.

---

## Step 4: Automate the Reminders (Cron-Job.org)

Because you cannot use "Windows Task Scheduler" on the live Linux server, we must use an external tool to trigger the PHP reminder system.

1. Go to [cron-job.org](https://cron-job.org/) and create a free account.
2. Click **Create Cronjob**.
3. In the URL field, paste your new live domain pointing to the reminder script:
   `http://meetdesk.epizy.com/api/cron/send-reminders-cron.php`
4. Set the execution schedule to **Every 15 minutes**.
5. Click **Create**.

---

## Final Verification
1. Visit your new live link (`meetdesk.epizy.com`).
2. Register a new test account.
3. Schedule a meeting.
4. Try to join the meeting from two different physical devices (like your phone and your laptop) to make sure the Video completely works over the internet and the Cloud Database successfully populated!
