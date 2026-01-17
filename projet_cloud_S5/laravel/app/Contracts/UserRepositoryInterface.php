<?php

namespace App\Contracts;

interface UserRepositoryInterface
{
    public function create(array $data);
    public function findByEmail(string $email);
    public function update(string $id, array $data);
    public function delete(string $id);
}