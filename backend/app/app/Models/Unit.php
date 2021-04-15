<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model {
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['address'];

    public function association() {
        return $this->belongsTo(Association::class);
    }

    public function owners() {
        return $this->belongsToMany(User::class);
    }
}
