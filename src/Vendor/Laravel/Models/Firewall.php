<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Firewall extends Eloquent
{
    protected $table = 'firewall';

    protected $guarded = [];
}
