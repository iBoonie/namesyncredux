<?php

// If file doesnt exist, just send /b/
function get_cache($file = 'board.cache')
{
    if (file_exists($file))
    {
        $get = file_get_contents($file);
        return ($get === false) ? 'b' : $get;
    } else {
        return 'b';
    }
}

function set_cache($data, $file = 'board.cache')
{
    if (file_put_contents($file, $data) === false)
    {
        return false;
    }

    return true;
}