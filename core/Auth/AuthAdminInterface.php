<?php

namespace Core\Auth;

interface AuthAdminInterface
{
    public function admin(): ?array;
    public function isAdmin(): bool;
}