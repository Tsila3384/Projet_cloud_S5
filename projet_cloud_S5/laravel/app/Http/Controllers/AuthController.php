<?php

namespace App\Http\Controllers;

use App\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\AuthException;

class AuthController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        try {
            $user = $this->userRepository->create($validated);
            
            return response()->json([
                'message' => 'Inscription réussie',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'inscription', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Erreur lors de l\'inscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $debugFirebase = $request->boolean('debug_firebase');

        try {
            // Chercher l'utilisateur dans PostgreSQL
            $user = $this->userRepository->findByEmail($validated['email']);

            if (!$user) {
                Log::info('Utilisateur non trouvé dans PostgreSQL', [
                    'email' => $validated['email']
                ]);
                
                return response()->json([
                    'message' => 'Identifiants invalides',
                    'debug' => $debugFirebase ? 'Utilisateur non trouvé dans la base de données' : null
                ], 401);
            }

            // Si l'utilisateur a un mot de passe local (PostgreSQL)
            if (!empty($user->password)) {
                if (!Hash::check($validated['password'], $user->password)) {
                    Log::info('Mot de passe PostgreSQL incorrect', [
                        'email' => $validated['email']
                    ]);
                    
                    return response()->json([
                        'message' => 'Identifiants invalides',
                        'debug' => $debugFirebase ? 'Mot de passe incorrect (PostgreSQL)' : null
                    ], 401);
                }
                
                Log::info('Connexion réussie via PostgreSQL', [
                    'email' => $validated['email']
                ]);
                
                return response()->json([
                    'message' => 'Connexion réussie',
                    'user' => $user,
                    'auth_method' => 'postgresql'
                ]);
            }

            // Si pas de mot de passe local, tenter Firebase
            Log::info('Tentative authentification Firebase', [
                'email' => $validated['email']
            ]);

            $firebaseInfo = $this->authenticateWithFirebase(
                $validated['email'],
                $validated['password'],
                $debugFirebase
            );

            $response = [
                'message' => 'Connexion réussie',
                'user' => $user,
                'auth_method' => 'firebase'
            ];

            if ($debugFirebase && $firebaseInfo) {
                $response['firebase'] = $firebaseInfo;
            }

            return response()->json($response);

        } catch (\Throwable $e) {
            Log::error('Erreur connexion', [
                'email' => $validated['email'] ?? 'N/A',
                'message' => $e->getMessage(),
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $payload = ['message' => 'Erreur lors de la connexion'];

            if ($debugFirebase) {
                $payload['error'] = $e->getMessage();
                $payload['type'] = get_class($e);
            }

            $status = $e instanceof AuthException ? 401 : 500;

            return response()->json($payload, $status);
        }
    }

    public function updateProfile(Request $request, string $userId)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'password' => 'sometimes|min:6|confirmed',
        ]);

        try {
            $user = $this->userRepository->update($userId, $validated);
            
            return response()->json([
                'message' => 'Profil mis à jour',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour profil', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function authenticateWithFirebase(string $email, string $password, bool $debug = false): ?array
    {
        // Vérifier si Firebase est disponible
        if (!app()->bound('firebase.auth') || !app('firebase.auth')) {
            Log::warning('Firebase Auth non disponible');
            throw new \RuntimeException('Firebase Auth non configuré ou désactivé');
        }

        $auth = app('firebase.auth');
        
        try {
            // Vérifier que l'utilisateur existe dans Firebase
            $userRecord = $auth->getUserByEmail($email);
            
            Log::info('Utilisateur trouvé dans Firebase', [
                'uid' => $userRecord->uid,
                'email' => $userRecord->email
            ]);
            
            // Authentifier avec Firebase
            $signInResult = $auth->signInWithEmailAndPassword($email, $password);
            
            Log::info('Authentification Firebase réussie');

            $payload = [
                'uid' => $userRecord->uid,
                'email' => $userRecord->email,
                'providers' => collect($userRecord->providerData ?? [])
                    ->map(fn ($provider) => $provider->providerId ?? null)
                    ->filter()
                    ->values()
                    ->all(),
            ];

            if ($debug) {
                return $payload;
            }

            return null;
            
        } catch (\Throwable $e) {
            Log::error('Erreur Firebase', [
                'email' => $email,
                'message' => $e->getMessage(),
                'type' => get_class($e)
            ]);
            
            throw $e;
        }
    }
}