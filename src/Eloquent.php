<?php

namespace Flysap\Scaffold;

use Flysap\Scaffold\Traits\ScaffoldTrait;
use Illuminate\Database\Eloquent\Model;

class Eloquent extends Model implements ScaffoldAble {

  use ScaffoldTrait;

    public $fillable = ['user', 'asdas'];
}