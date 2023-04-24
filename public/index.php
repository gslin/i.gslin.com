<?php

call_user_func(function(){
    # Prevent from session fixation.
    session_start();
    if ($_SESSION['valid'] !== 1) {
        session_regenerate_id();
    }
});
