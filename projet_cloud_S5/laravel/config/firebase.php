<?php

return [
    /*
     * ------------------------------------------------------------------------
     * Enable/Disable Firebase
     * ------------------------------------------------------------------------
     * 
     * Permet d'activer ou désactiver Firebase sans supprimer la configuration
     */
    'enabled' => env('FIREBASE_ENABLED', false),

    /*
     * ------------------------------------------------------------------------
     * Firebase credentials
     * ------------------------------------------------------------------------
     * 
     * Chemin vers le fichier JSON de credentials Firebase
     * Exemple: storage/app/firebase-credentials.json
     */
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS'),
    ],

    /*
     * ------------------------------------------------------------------------
     * Firebase project ID
     * ------------------------------------------------------------------------
     */
    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
     * ------------------------------------------------------------------------
     * Firebase Database URL
     * ------------------------------------------------------------------------
     */
    'database' => [
        'url' => env('FIREBASE_DATABASE_URL'),
    ],
];