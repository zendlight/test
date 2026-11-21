<?php

namespace Core\Auth;

interface AuthUserInterface
{
    public function user(): ?array;
    public function isUser(): bool;	
    public function remember(int $userId): void;	
    public function autoLogin(): bool;	
	
	
	
}