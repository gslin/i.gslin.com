<?php

require __DIR__ . '/../vendor/autoload.php';

call_user_func(function () {
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
        $mode = $_POST['mode'];

        if ($mode === 'file') {
            $file = $_FILES['file'];

            switch ($file['type']) {
                case 'image/bmp':
                    $imgtype = 'image/bmp';
                    $img = imagecreatefrombmp($file['tmp_name']);
                    break;
                case 'image/gif':
                    $imgtype = 'image/gif';
                    $img = imagecreatefromgif($file['tmp_name']);
                    break;
                case 'image/png':
                    $imgtype = 'image/png';
                    $img = imagecreatefrompng($file['tmp_name']);
                    break;
                case 'image/webp':
                    $imgtype = 'image/webp';
                    $img = imagecreatefromwebp($file['tmp_name']);
                    break;
                case 'image/svg+xml':
                    header('Status: 403');
                    return;
                default:
                    header('Status: 400');
                    return;
            }
        }

        if ($mode === 'url') {
            $url = $_POST['url'];

            $client = new GuzzleHttp\Client();
            $res = $client->request('GET', $url);
            $body = $res->getBody();

            // quit if error
            if ($res->getStatusCode() !== 200) {
                header('Status: 400');
                return;
            }

            $content_type = $res->getHeader('Content-Type')[0];
            $content_type = explode(';', $content_type)[0];

            switch ($content_type) {
                case 'image/bmp':
                case 'image/gif':
                case 'image/png':
                case 'image/webp':
                    $imgtype = $content_type;
                    $img = imagecreatefromstring($body);
                    break;
                default:
                    header('Status: 400');
                    return;
            }
        }

        $outfilename = sprintf('s/%d-%s', time(), bin2hex(random_bytes(4)));

        switch ($imgtype) {
            case 'image/bmp':
            case 'image/png':
            case 'image/webp':
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
            case 'image/gif':
                $outfilename_gif = sprintf('%s.gif', $outfilename);

                if ($mode === 'url') {
                    file_put_contents($outfilename_gif, $body);
                } else {
                    imagegif($img, $outfilename_gif);
                }

                header('Status: 302');
                header("Location: /${outfilename_gif}");

                return;
            default:
                header('Status: 400');
                return;
        }
    }

    header('Status: 405');
    echo 'method not allowed';
});
