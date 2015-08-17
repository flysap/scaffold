<?php

namespace Flysap\Scaffold;

/**
 * Change array keys recursive .
 *
 * @param $arr
 * @return array
 */
function array_change_key_case_recursive($arr) {
    return array_map(function ($item) {
        if (is_array($item))
            $item = array_change_key_case_recursive($item);
        return $item;
    }, array_change_key_case($arr));
}

