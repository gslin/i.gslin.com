<?php

call_user_func(function(){
    # Prevent from session fixation.
    session_start();
    if (!isset($_SESSION['valid'])) {
        session_regenerate_id();
        $_SESSION['valid'] = 1;
    }

    # GET part.
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: text/html');
        $str = file_get_contents(__DIR__ . '/../templates/upload.html');
        $csrf_token = hash('sha256', session_id());
        $str = str_replace('%CSRF_TOKEN%', $csrf_token, $str);
        echo $str;
        return;
    }

    # POST part.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = hash('sha256', session_id());
        if ($_POST['csrf_token'] != $csrf_token) {
            header('Status: 400');
            echo 'csrf_token invalid';
            return;
        }

        if (!isset($_FILES['file'])) {
            header('Status: 400');
            echo 'miss file';
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

    header('Status: 405');
    echo 'method not allowed';
});
