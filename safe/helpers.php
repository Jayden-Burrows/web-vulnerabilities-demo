<?php

// Wrapper function for htmlspecialchars, just shortens the function call inside the php pages
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Sends a JSON response and stops
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

// Reads a request's value only if it is a plain string value
// blocks attackers where a user might input something like '?post_id[]=x'
function str_param(array $source, string $key): string
{
    $v = $source[$key] ?? '';
    return is_string($v) ? $v : '';
}
