<?php
// google_config.example.php
// Copy this file to google_config.php and set your Google OAuth client credentials

$google_client_id = getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID';
$google_client_secret = getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET';
$google_redirect_uri = getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/BookwagonNew1/google_callback.php';

// Generate Google Login URL
$google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $google_client_id,
    'redirect_uri' => $google_redirect_uri,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online',
    'prompt' => 'select_account'
]);
