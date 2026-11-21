<?php

namespace Core;

/**
 * ---------------------------------------------------------
 * HTTP Response
 * ---------------------------------------------------------
 *
 * Управление:
 *
 * - redirect
 * - headers
 * - status codes
 * - output
 * - json
 *
 * ---------------------------------------------------------
 */

class Response
{
    /**
     * HTTP status code
     */
    protected int $statusCode = 200;

    /**
     * Headers
     */
    protected array $headers = [];

    /**
     * Response content
     */
    protected string $content = '';

    /**
     * ---------------------------------------------------------
     * Set status code
     * ---------------------------------------------------------
     */

    public function setStatusCode(
        int $code
    ): self {

        $this->statusCode = $code;

        return $this;
    }

    /**
     * ---------------------------------------------------------
     * Set header
     * ---------------------------------------------------------
     */

    public function header(
        string $name,
        string $value
    ): self {

        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * ---------------------------------------------------------
     * Redirect
     * ---------------------------------------------------------
     */

    public function redirect(
        string $url,
        int $statusCode = 302
    ): void {

        $this->setStatusCode(
            $statusCode
        );

        header(
            "Location: {$url}",
            true,
            $statusCode
        );

        exit;
    }

    /**
     * ---------------------------------------------------------
     * JSON response
     * ---------------------------------------------------------
     */

    public function json(
        array $data,
        int $statusCode = 200
    ): void {

        $this->setStatusCode(
            $statusCode
        );

        header(
            'Content-Type: application/json'
        );

        http_response_code(
            $statusCode
        );

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
            | JSON_PRETTY_PRINT
        );

        exit;
    }

    /**
     * ---------------------------------------------------------
     * Set content
     * ---------------------------------------------------------
     */

    public function setContent(
        string $content
    ): self {

        $this->content = $content;

        return $this;
    }

    /**
     * ---------------------------------------------------------
     * Send response
     * ---------------------------------------------------------
     */

    public function send(): void
    {
        //---------------------------------------------
        // Status code
        //---------------------------------------------

        http_response_code(
            $this->statusCode
        );

        //---------------------------------------------
        // Headers
        //---------------------------------------------

        foreach (
            $this->headers
            as $name => $value
        ) {

            header(
                "{$name}: {$value}"
            );
        }

        //---------------------------------------------
        // Content
        //---------------------------------------------

        echo $this->content;
    }
}


/*

Пока send() можно почти не использовать

Потому что View уже делает output.

Но позже можно будет перейти к:

$response
    ->setContent($html)
    ->send();
И тогда:
API,
AJAX,
JSON,
downloads,
headers,

станут намного cleaner.

Особенно полезно later для:
JSON API
return $this->response->json([
    'success' => true
]);
Custom status
$this->response
    ->setStatusCode(404);
File downloads
API module
AJAX validation

*/