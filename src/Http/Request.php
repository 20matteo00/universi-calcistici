<?php

namespace App\Http;

class Request
{
    public string $metodo;
    public string $path;
    public array $query;
    public array $body;

    public function __construct()
    {
        $this->metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url($uri, PHP_URL_PATH) ?? '/';

        $this->query = $_GET;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $raw = file_get_contents('php://input');

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($raw, true);
            $this->body = is_array($decoded) ? $decoded : [];
            return;
        }

        $this->body = $_POST;
    }
}