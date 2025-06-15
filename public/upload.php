<?php

require __DIR__ . '/../vendor/autoload.php';

call_user_func(function(){
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    $sentry_dsn = $_ENV['SENTRY_DSN'];

    Sentry\init(['dsn' => $sentry_dsn]);

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

        if (!isset($_POST['mode'])) {
            header('Status: 400');
            echo 'missing mode field';
            return;
        }

        if ($_POST['mode'] === 'file') {
            $file = $_FILES['file'];

            switch ($file['type']) {
            case 'image/bmp':
                $img = imagecreatefrombmp($file['tmp_name']);
                break;
            case 'image/png':
                $img = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/svg+xml':
                header('Status: 403');
                return;
            default:
                header('Status: 400');
                return;
            }
        }

        if ($_POST['mode'] === 'url') {
            $url = $_POST['url'];

            $client = new GuzzleHttp\Client();
            $res = $client->request('GET', $url);
            $body = $res->getBody();

            // quit if error
            if ($res->getStatusCode() !== 200) {
                header('Status: 400');
                return;
            }

            $img = imagecreatefromstring($body);
        }

        $outfilename = sprintf('s/%d-%s', time(), bin2hex(random_bytes(4)));
        $outfilename_jpeg = sprintf('%s.jpeg', $outfilename);
        $outfilename_png = sprintf('%s.png', $outfilename);
        $outfilename_webp = sprintf('%s.webp', $outfilename);

        imagejpeg($img, $outfilename_jpeg, $quality = 75);
        imagepng($img, $outfilename_png, $quality = 9);

        # convert before webp output:
        imagepalettetotruecolor($img);
        imagewebp($img, $outfilename_webp, $quality = 100);

        header('Status: 302');
        header("Location: /${outfilename_png}");
        return;
    }

    header('Status: 405');
    echo 'method not allowed';
});
