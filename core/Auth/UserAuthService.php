<?php
namespace Core\Auth;

class UserAuthService
    extends AbstractAuthService
    implements AuthUserInterface
{
    protected string $sessionKey = 'user';

//-----------------------------------------

    public function isUser(): bool
    {
        return $this->check();
    }
//-----------------------------------------

    public function user(): ?array
    {
        return $this->identity();
    }
//-----------------------------------------

public function remember(int $userId): void 
{
/*
	позже можно сделать:
	RememberMeService.php
*/
    //---------------------------------------------
    // Generate token
    //---------------------------------------------

    $token =
        bin2hex(
            random_bytes(32)
        );

    //---------------------------------------------
    // Expiration
    //---------------------------------------------

    $expires =
        date(
            'Y-m-d H:i:s',
            time() + (3600 * 24 * 30)
        );

    //---------------------------------------------
    // Save to DB
    //---------------------------------------------

    $storage = new \User\Storage\Pdo\UserStorage();

    $storage->update(
        $userId,

        [
            'remember_token'   => $token,
            'remember_expires' => $expires
        ]
    );

    //---------------------------------------------
    // Set cookie
    //---------------------------------------------

    setcookie(

        'remember_token',

        $token,

        [
            'expires'  => time() + (3600 * 24 * 30),

            'path'     => '/',

            'httponly' => true,

            'samesite' => 'Lax'
        ]
    );
}	

/**
 * ---------------------------------------------------------
 * Auto login from cookie
 * ---------------------------------------------------------
 */

public function autoLogin(): bool
{
    //---------------------------------------------
    // Already logged in
    //---------------------------------------------

    if ($this->isLoggedIn()) {

        return true;
    }

    //---------------------------------------------
    // Cookie exists?
    //---------------------------------------------

    $token =
        $_COOKIE['remember_token']
        ?? null;

    if (!$token) {

        return false;
    }

    //---------------------------------------------
    // Find user
    //---------------------------------------------

    $storage =
        new \User\Storage\Pdo\UserStorage();

    $user =
        $storage->findByRememberToken(
            $token
        );

    if (!$user) {

        return false;
    }

    //---------------------------------------------
    // Expired?
    //---------------------------------------------

    if (
        strtotime(
            $user['remember_expires']
        ) < time()
    ) {

        return false;
    }

    //---------------------------------------------
    // Restore session
    //---------------------------------------------

    $this->login($user);

    return true;
}	
	
	




	
}