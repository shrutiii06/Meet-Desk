<?php
/**
 * MONGODB DATABASE CONFIGURATION
 * 
 * This file defines the MongoDB connection string
 * All backend files use this to connect to MongoDB
 */

if (!defined('MONGODB_URI')) {
    // Local MongoDB connection (Running on Port 27017)
    define('MONGODB_URI', 'mongodb://localhost:27017');
    
    // Note: Database name is 'meetdesk'
    // Note: Collection name is 'users'
}