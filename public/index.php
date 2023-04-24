<?php

call_user_func(function(){
    # Prevent from session fixation.
    session_start();
    if ($_SESSION['valid'] !== 1) {
        session_regenerate_id();
        $_SESSION['valid'] = 1;
    }

    # A simple routing for "/upload".
    if ($_SERVER['REQUEST_METHOD'] === 'GET' and $_SERVER['REQUEST_URI'] === '/upload') {
        header('Content-Type: text/html');
        include(__DIR__ . '/../templates/upload.phtml');
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' and $_SERVER['REQUEST_URI'] === '/upload') {
        if (!isset($_FILES['file'])) {
            header('Status: 400');
            return;
        }
        $file = $_FILES['file'];

        return;
    }

    header('Status: 404');
});
