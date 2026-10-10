<?php

function start_vuln_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    } else {
        session_name('VULNSESSID');
        session_start();
    }
}