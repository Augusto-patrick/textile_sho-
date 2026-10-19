<?php
// ============================================================
// CSRF (Cross-Site Request Forgery) protection helpers
//
// HOW IT WORKS:
//  1. When a page renders a form, csrf_field() prints a hidden
//     input holding a random token stored in the user's session.
//  2. When the form is submitted, csrf_verify() compares the
//     submitted token with the session token using hash_equals()
//     (timing-safe). A site on another domain can read its OWN
//     forms, but cannot read OUR session token - so it cannot
//     produce a valid token, and the request is rejected.
//
// NOTE ON PHP COMMENTS: the sequence "question-mark greater-than"
// (the closing PHP tag) inside a // comment ends PHP mode and
// breaks the file, so it is deliberately avoided in this file.
//
// USAGE - in any page that renders a form:
//     <form method="POST" action="save.php">
//         /* then print: <?= csrf_field() */  (i.e. the csrf_field call)
//         ...
//     </form>
//
// USAGE - at the top of the matching POST handler:
//     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//         csrf_verify();   // dies with 403 on bad/missing token
//         ...
//     }
//
// USAGE - for links that perform mutations via GET (e.g. deletes):
//     href="delete.php?id=7&t=ENCODED_TOKEN"
//     where ENCODED_TOKEN is urlencode(csrf_token()) printed at
//     render time, and the handler calls csrf_verify_get();
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    // Harden the session cookie BEFORE session_start():
    //   httponly = true  -> JavaScript cannot read the cookie (blocks XSS theft)
    //   samesite = Lax   -> browser won't send the cookie on cross-site POSTs
    //   secure   -> change to true when the site runs on HTTPS (false on localhost)
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,          // cookie lives until the browser closes
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,      // change to true behind HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params(0, '/', '', false, true);
    }
    session_start();
    // Note: session id rotation is done at LOGIN time (see login.php /
    // storefront login & register), which is where it actually defends
    // against session fixation - not here at session creation.
}

// Returns this session's token, creating it on first use.
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Prints the hidden input to place inside a form.
// Called from HTML like:  form echo csrf_field.
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="'
         . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

// Call at the top of every POST handler. Rejects the request (419)
// if the token is missing or does not match the session.
function csrf_verify() {
    $sent     = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if (!is_string($sent) || $sent === '' || $expected === '' || !hash_equals($expected, $sent)) {
        // 403 (not 419): Apache serves standard reason phrases for
        // well-known codes; an unknown code like 419 can surface as 500.
        http_response_code(403);
        die('Your session token is missing or expired. Please go back, refresh the page, and try again.');
    }
}

// Call at the top of GET handlers that change data (delete links etc.).
function csrf_verify_get() {
    $sent     = $_GET['t'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if (!is_string($sent) || $sent === '' || $expected === '' || !hash_equals($expected, $sent)) {
        http_response_code(403);
        die('Invalid or missing security token. Please use the links on the page itself.');
    }
}
