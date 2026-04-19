<?php
if (!defined('MONGODB_URI')) {
    define('MONGODB_URI', getenv('MONGODB_URI') ?: 'mongodb://localhost:27017');
}
