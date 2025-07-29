<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class TestModel extends Model
{
    // Explicitly set the connection if it's not your default
    protected $connection = 'mongodb';

    // Set the collection name
    protected $table = 'test';

    // Define which fields can be mass-assigned (optional but good practice)
    protected $fillable = ['key', 'value'];
}
