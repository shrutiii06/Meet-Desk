/**
 * MONGODB SCHEMA UPDATE
 * 
 * New fields to add to meetings collection
 * Run this to understand the structure
 */

// ===== CURRENT MEETINGS COLLECTION SCHEMA =====
// Existing fields (keep these):
{
  _id: ObjectId,
  userEmail: "host@company.com",
  userName: "John Doe",
  topic: "Meeting Topic",
  description: "Meeting description",
  date: "2026-02-26",
  time: "10:00",
  duration: 60,
  timezone: "UTC",
  repeat: "never",
  enableWaitingRoom: boolean,
  autoRecord: boolean,
  addToCalendar: boolean,
  status: "scheduled",
  createdAt: ISODate,
  scheduledAt: ISODate
}

// ===== NEW FIELDS TO ADD =====
// Add these fields to existing meetings collection:

{
  // ===== MEETING CREDENTIALS (Like Zoom) =====
  meetingId: "MIT20260226ASX9L2K",     // Unique meeting ID
  password: "123456",                  // 6-digit password
  joinLink: "meetdesk.com/join/MIT20260226ASX9L2K",  // Full join URL
  
  // ===== PUBLIC vs PRIVATE MODE =====
  isPublic: false,                     // true = Webinar (no pre-notification)
                                       // false = Team meeting (notify attendees)
  
  // ===== ATTENDEE MANAGEMENT =====
  attendeeEmails: [                    // Array of emails to notify (empty if public)
    "jane@company.com",
    "bob@company.com"
  ],
  
  // ===== EMAIL NOTIFICATION TRACKING =====
  attendeesSent: false,                // Has scheduled email been sent?
  reminderSent: false,                 // Has 30-min reminder been sent?
  editNotificationSent: false,         // Has edit/delete notification been sent?
  
  // ===== RECURRING MEETINGS =====
  recurringParentId: ObjectId,         // If this is a recurring meeting, link to parent
  recurringOccurrence: 1,              // Which occurrence in the series (1, 2, 3, etc)
  
  // ===== TIMESTAMPS FOR SCHEDULING =====
  scheduledDateTime: ISODate,          // Combined date + time for easier queries
  reminderScheduledFor: ISODate        // When to send 30-min reminder
}

// ===== COMPLETE UPDATED SCHEMA =====

db.meetings.insertMany([
  {
    // Original fields
    _id: ObjectId("507f1f77bcf86cd799439011"),
    userEmail: "host@company.com",
    userName: "John Doe",
    topic: "Q1 Planning",
    description: "Quarterly planning session",
    date: "2026-02-26",
    time: "10:00",
    duration: 60,
    timezone: "UTC",
    repeat: "never",
    enableWaitingRoom: true,
    autoRecord: true,
    addToCalendar: true,
    status: "scheduled",
    createdAt: ISODate("2026-02-25T14:30:00Z"),
    scheduledAt: ISODate("2026-02-26T10:00:00Z"),
    
    // NEW FIELDS
    meetingId: "MIT20260226ASX9L2K",
    password: "123456",
    joinLink: "meetdesk.com/join/MIT20260226ASX9L2K",
    isPublic: false,
    attendeeEmails: [
      "jane@company.com",
      "bob@company.com",
      "alice@company.com"
    ],
    attendeesSent: false,
    reminderSent: false,
    editNotificationSent: false,
    recurringParentId: null,
    recurringOccurrence: 1,
    scheduledDateTime: ISODate("2026-02-26T10:00:00Z"),
    reminderScheduledFor: ISODate("2026-02-26T09:30:00Z")
  }
])

// ===== NEW COLLECTION: participant_logs =====
// Track who attended which meetings

db.createCollection("participant_logs", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["meetingId", "participantId", "joinedAt"],
      properties: {
        _id: { bsonType: "objectId" },
        meetingId: { bsonType: "objectId" },
        participantId: { bsonType: "string" },
        participantName: { bsonType: "string" },
        participantEmail: { bsonType: "string" },
        joinedAt: { bsonType: "date" },
        leftAt: { bsonType: "date" },
        durationMinutes: { bsonType: "int" }
      }
    }
  }
})

// ===== INDEXED QUERIES =====
// Add these indexes for faster queries

db.meetings.createIndex({ meetingId: 1 })  // Find meeting by ID
db.meetings.createIndex({ userEmail: 1 })  // Find user's meetings
db.meetings.createIndex({ status: 1, scheduledDateTime: 1 })  // Find upcoming meetings
db.meetings.createIndex({ isPublic: 1 })  // Find public meetings
db.meetings.createIndex({ attendeesSent: 1, reminderScheduledFor: 1 })  // Find pending reminders

db.participant_logs.createIndex({ meetingId: 1 })
db.participant_logs.createIndex({ participantEmail: 1 })
