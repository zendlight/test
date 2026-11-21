<?php
namespace Core\Auth;

abstract class AbstractAuthService
{
    protected string $sessionKey;

//-----------------------------------------

    public function login(array $identity): void
    {
        $_SESSION[$this->sessionKey] = $identity;
    }
//-----------------------------------------

    public function logout(): void
    {
        unset($_SESSION[$this->sessionKey]);
    }
//-----------------------------------------

    public function check(): bool
    {
        return isset($_SESSION[$this->sessionKey]);
    }
	
// то же самое, alias:
	public function isLoggedIn(): bool
	{
		return $this->check();
	}
//-----------------------------------------

    public function identity(): ?array
    {
        return $_SESSION[
            $this->sessionKey
        ] ?? null;
    }
//-----------------------------------------

    public function id(): ?int
    {
        return $this->identity()['id']
            ?? null;
    }
//-----------------------------------------
	
	public function getRole(): ?string
	{
		return $this->identity()['role'] ?? null;
	}
//-----------------------------------------

	public function hasRole_old(string $role): bool
	{
		return $this->getRole() === $role;
	}
	
	public function hasRole(array|string $roles): bool
	{
		$current = $this->getRole();

		if (is_array($roles)) {
			return in_array($current, $roles);
		}

		return $current === $roles;
	}
	
	/*
	$auth->hasRole('admin')

	$auth->hasRole(['admin','moderator'])
	*/
//-----------------------------------------


//-----------------------------------------


//-----------------------------------------


//-----------------------------------------

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}



