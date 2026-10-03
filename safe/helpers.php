<?php

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_out(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function json_fail(string $message, int $status = 400): never
{
    json_out(['success' => false, 'error' => $message], $status);
}

function str_param(array $source, string $key): string
{
    $v = $source[$key] ?? '';
    return is_string($v) ? $v : '';
}
