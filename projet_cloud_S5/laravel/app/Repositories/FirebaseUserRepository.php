<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Hash;

class FirebaseUserRepository implements UserRepositoryInterface
{
    protected $auth;
    protected $database;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
        $this->auth = $factory->createAuth();
        $this->database = $factory->createDatabase();
    }

    public function create(array $data)
    {
        $userProperties = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        $createdUser = $this->auth->createUser($userProperties);

        // Stocker les infos supplémentaires dans Realtime Database
        $this->database->getReference('users/' . $createdUser->uid)
            ->set([
                'name' => $data['name'] ?? '',
                'email' => $data['email'],
                'created_at' => now()->toIso8601String(),
            ]);

        return $createdUser;
    }

    public function findByEmail(string $email)
    {
        try {
            return $this->auth->getUserByEmail($email);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function update(string $id, array $data)
    {
        $properties = [];
        
        if (isset($data['email'])) {
            $properties['email'] = $data['email'];
        }
        
        if (isset($data['password'])) {
            $properties['password'] = $data['password'];
        }

        $this->auth->updateUser($id, $properties);

        // Mettre à jour les autres infos
        $updateData = array_filter($data, fn($key) => !in_array($key, ['password']), ARRAY_FILTER_USE_KEY);
        
        if (!empty($updateData)) {
            $this->database->getReference('users/' . $id)
                ->update($updateData);
        }

        return $this->auth->getUser($id);
    }

    public function delete(string $id)
    {
        $this->auth->deleteUser($id);
        $this->database->getReference('users/' . $id)->remove();
        return true;
    }
}