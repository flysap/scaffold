<?php

namespace Flysap\Scaffold;

interface BuildAble {

    public function render($group = null);

    public function build();
}