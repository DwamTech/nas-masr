<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the service account JSON file downloaded from Firebase Console
    |
    */
    'credentials' => env(
        'FIREBASE_CREDENTIALS',
        storage_path('app/firebase/nasmasr-app-firebase-adminsdk-fbsvc-081471eca8.json')
    ),

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL (Optional)
    |--------------------------------------------------------------------------
    |
    | Only needed if you're using Firebase Realtime Database
    |
    */
    'database_url' => env('FIREBASE_DATABASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Firebase Server Key (Legacy - Not Used)
    |--------------------------------------------------------------------------
    |
    | This is kept for reference but we're using Service Account instead
    |
    */
    'server_key' => env('FIREBASE_SERVER_KEY', ''),
];
