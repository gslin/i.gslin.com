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

        switch ($file['type']) {
        case 'image/bmp':
            $img = imagecreatefrombmp($file['tmp_name']);
            break;
        case 'image/png':
            $img = imagecreatefrompng($file['tmp_name']);
            break;
        default:
            header('Status: 400');
            return;
        }

        $outfilename = sprintf('s/%d-%s', time(), bin2hex(random_bytes(4)));
        $outfilename_jpeg = sprintf('%s.jpeg', $outfilename);
        $outfilename_png = sprintf('%s.png', $outfilename);
        $outfilename_webp = sprintf('%s.webp', $outfilename);
        imagejpeg($img, $outfilename_jpeg, $quality = 75);
        imagepng($img, $outfilename_png, $quality = 9);
        imagewebp($img, $outfilename_webp, $quality = 100);

        $data = [
            'images' => [
                'jpeg' => $outfilename_jpeg,
                'png' => $outfilename_png,
                'webp' => $outfilename_webp,
            ],
        ];

        header('Content-Type: application/json');
        echo json_encode($data);
        return;
    }

    header('Status: 404');
});
