/**
 * DELETE SPECIFIC MEETING
 * 
 * Deletes a specific meeting if it has passed its end time
 * Call this function when user clicks "Delete" on a meeting
 * or when the meeting time has passed
 * 
 * Usage in HTML:
 * <script src="js/meeting-cleanup.js"></script>
 * <script>
 *   // Delete a specific meeting
 *   deleteMeeting(userEmail, meetingId);
 * </script>
 */

// ===== DELETE SPECIFIC MEETING =====
async function deleteMeeting(userEmail, meetingId) {
  try {
    console.log('🗑️ Deleting meeting:', meetingId);
    
    const response = await fetch(`${API_URL}/meetings/cleanup-expired.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: userEmail,
        meetingId: meetingId
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      console.log('✅ Meeting deleted:', data.meetingTitle);
      
      if (data.wasExpired) {
        console.log('⏰ ' + data.meetingTitle + ' was expired and has been removed');
      } else {
        console.log('🗑️ ' + data.meetingTitle + ' has been deleted');
      }
      
      // Refresh the meetings list
      location.reload();
      
    } else {
      console.log('❌ Delete failed:', data.error);
      alert('Error: ' + data.error);
    }
  } catch (error) {
    console.log('❌ Delete error:', error.message);
    alert('Network error: ' + error.message);
  }
}

// ===== CHECK IF MEETING IS EXPIRED =====
function isMeetingExpired(endTime) {
  if (!endTime) return false;
  
  // endTime should be a JavaScript Date object or ISO string
  const endDate = new Date(endTime);
  const now = new Date();
  
  return endDate < now;
}

// ===== AUTO-DELETE EXPIRED MEETINGS FROM UI =====
// Call this when displaying meetings to hide/remove expired ones
function hideExpiredMeetings() {
  const meetingElements = document.querySelectorAll('[data-meeting-end-time]');
  
  meetingElements.forEach(element => {
    const endTime = element.getAttribute('data-meeting-end-time');
    
    if (isMeetingExpired(endTime)) {
      console.log('⏰ Hiding expired meeting');
      element.style.opacity = '0.5';
      element.innerHTML += '<span class="text-red-500 ml-2">[EXPIRED]</span>';
    }
  });
}
