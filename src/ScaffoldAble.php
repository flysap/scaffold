<?php

namespace Flysap\Scaffold;

interface ScaffoldAble {

    /**
     * Return an array with fields needs to be editable .
     *
     * @return mixed
     */
    public function skyEdit();

    /**
     * Return an array with fields needs to be filtered .
     *
     * @return mixed
     */
    public function skyFilter();

    /**
     * Return an array with fields is needed to be listed .
     *
     * @return mixed
     */
    public function skyShow();
}