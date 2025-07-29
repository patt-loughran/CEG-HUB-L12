<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Globals extends Model
{
      // Explicitly set the connection if it's not your default
    protected $connection = 'mongodb';

    // Set the collection name
    protected $table = 'globals';
}
