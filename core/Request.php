<?php

namespace Core;

/**
 * -------------------------------------
 * Request
 * -------------------------------------
 *
 * HTTP Request object.
 *
 * -------------------------------------
 */

class Request
{
    /**
     * -------------------------------------
     * Request data
     * -------------------------------------
     */

    protected array $get;

    protected array $post;

    protected array $server;

    protected array $cookies;

    protected array $session;

    /**
     * -------------------------------------
     * Route params
     * -------------------------------------
     */

    protected array $route = [];

    /**
     * -------------------------------------
     * Method / URI
     * -------------------------------------
     */

    protected string $method;

    protected string $uri;

    /**
     * -------------------------------------
     * Constructor
     * -------------------------------------
     */

    public function __construct()
    {
        $this->get     = $_GET;

        $this->post    = $_POST;

        $this->server  = $_SERVER;

        $this->cookies = $_COOKIE;

        $this->session = $_SESSION ?? [];

        $this->method =
            $_SERVER['REQUEST_METHOD']
            ?? 'GET';

        $this->uri =
            $_SERVER['REQUEST_URI']
            ?? '/';
    }

    /**
     * -------------------------------------
     * Universal input
     * -------------------------------------
     */

    public function input(
        string $key,

        mixed $default = null
    ): mixed {

        return

            $this->post[$key]

            ?? $this->get[$key]

            ?? $default;
    }

    /**
     * -------------------------------------
     * GET param
     * -------------------------------------
     */

    public function get(
        string $key,

        mixed $default = null
    ): mixed {

        return
            $this->get[$key]
            ?? $default;
    }

    /**
     * -------------------------------------
     * POST param
     * -------------------------------------
     */

    public function post(
        string $key,

        mixed $default = null
    ): mixed {

        return
            $this->post[$key]
            ?? $default;
    }

    /**
     * -------------------------------------
     * Uploaded file
     * -------------------------------------
     */

    public function file(
        string $key
    ): ?array {

        return
            $_FILES[$key]
            ?? null;
    }

    /**
     * -------------------------------------
     * Cookie
     * -------------------------------------
     */

    public function cookie(
        string $key,

        mixed $default = null
    ): mixed {

        return
            $this->cookies[$key]
            ?? $default;
    }

    /**
     * -------------------------------------
     * Session
     * -------------------------------------
     */

    public function session(
        string $key,

        mixed $default = null
    ): mixed {

        return
            $this->session[$key]
            ?? $default;
    }

    /**
     * -------------------------------------
     * Server param
     * -------------------------------------
     */

    public function server(
        string $key,

        mixed $default = null
    ): mixed {

        return
            $this->server[$key]
            ?? $default;
    }

    /**
     * -------------------------------------
     * HTTP method
     * -------------------------------------
     */

    public function method(): string
    {
        return $this->method;
    }

    /**
     * -------------------------------------
     * Is POST
     * -------------------------------------
     */

    public function isPost(): bool
    {
        return
            $this->method()
            === 'POST';
    }

    /**
     * -------------------------------------
     * URI
     * -------------------------------------
     */

    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * -------------------------------------
     * Set route params
     * -------------------------------------
     */

    public function setRoute(array $route): void 
	{

        $this->route = $route;
    }

    /**
     * -------------------------------------
     * Route param(s)
     * -------------------------------------
     */

    public function route(
        ?string $key = null,

        mixed $default = null
    ): mixed {

        //---------------------------------------------
        // All route params
        //---------------------------------------------

        if ($key === null) {

            return $this->route;
        }

        //---------------------------------------------
        // Single route param
        //---------------------------------------------

        return
            $this->route[$key]
            ?? $default;
    }

    /**
     * -------------------------------------
     * Alias for route()
     * -------------------------------------
     *
     * Cleaner syntax:
     *
     * $request->param('id')
     *
     * -------------------------------------
     */

    public function param(
        string $key,

        mixed $default = null
    ): mixed {

        return
            $this->route(
                $key,
                $default
            );
    }
}