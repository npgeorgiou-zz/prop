<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Association extends Model {
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['name', 'address'];

    public function units() {
        return $this->hasMany(Unit::class);
    }

    public function admins() {
        return $this->belongsToMany(User::class);
    }

}
