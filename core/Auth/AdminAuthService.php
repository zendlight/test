<?php
namespace Core\Auth;

class AdminAuthService
    extends AbstractAuthService
    implements AuthAdminInterface
{
    protected string $sessionKey = 'admin';

//-----------------------------------------

    public function isAdmin(): bool
    {
        return $this->check();
    }
//-----------------------------------------

    public function admin(): ?array
    {
        return $this->identity();
    }
}