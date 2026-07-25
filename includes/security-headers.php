<?php

declare(strict_types=1);

if (headers_sent()) {
    return;
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

header(
    "Content-Security-Policy: "
    . "default-src 'self'; "
    . "base-uri 'self'; "
    . "form-action 'self' https://checkout.stripe.com; "
    . "frame-ancestors 'self'; "
    . "object-src 'none'; "
    . "img-src 'self' data: https:; "
    . "font-src 'self' data: https://cdnjs.cloudflare.com; "
    . "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
    . "script-src 'self' 'unsafe-inline'; "
    . "connect-src 'self' https://api.stripe.com; "
    . "frame-src https://checkout.stripe.com https://js.stripe.com;"
);