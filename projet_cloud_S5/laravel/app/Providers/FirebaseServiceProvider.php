<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('firebase.auth', function ($app) {
            // Vérifier si Firebase est activé
            if (!config('firebase.enabled', false)) {
                Log::info('Firebase est désactivé dans la configuration');
                return null;
            }

            try {
                $credentialsPath = config('firebase.credentials.file');
                
                // Vérifier si le fichier de credentials existe
                if (!$credentialsPath) {
                    Log::warning('Aucun chemin de credentials Firebase défini');
                    return null;
                }

                if (!file_exists($credentialsPath)) {
                    Log::warning('Fichier de credentials Firebase introuvable', [
                        'path' => $credentialsPath
                    ]);
                    return null;
                }

                // Créer l'instance Firebase Auth
                $factory = (new Factory)->withServiceAccount($credentialsPath);
                
                Log::info('Firebase Auth initialisé avec succès');
                
                return $factory->createAuth();
                
            } catch (\Throwable $e) {
                Log::error('Erreur lors de l\'initialisation de Firebase Auth', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                
                // Retourner null au lieu de lancer une exception
                // pour ne pas bloquer l'application
                return null;
            }
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}