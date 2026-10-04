<?php
// Small shared helpers for the SAFE version of the site.

/**
 * Escape a value for output in HTML (text nodes AND quoted attributes).
 * Use this for EVERYTHING that came from the database or the request,
 * including fields that "look" harmless like dates, locations and IDs.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Send a JSON response and stop. Errors use the same shape everywhere:
 *   {"success": false, "error": "..."}
 */
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

/** Read a request value only if it is a plain string (blocks ?post_id[]=x tricks). */
function str_param(array $source, string $key): string
{
    $v = $source[$key] ?? '';
    return is_string($v) ? $v : '';
}
