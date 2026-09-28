<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Playbooks\Traits\HasPlaybook;

class Product extends Model
{
    use HasPlaybook;

    protected $guarded = ['id'];
}
