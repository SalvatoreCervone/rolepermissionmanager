<?php

namespace Workbench\App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use SalvatoreCervone\RolePermissionManager\Traits\HasAcl;

class User extends Authenticatable
{
    use HasAcl;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];
}
