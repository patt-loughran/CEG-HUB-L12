<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class PinnedProject extends Model
{
      // Explicitly set the connection if it's not your default
    protected $connection = 'mongodb';

    // Set the collection name
    protected $table = 'pinned_projects';

    // Define which fields can be mass-assigned (optional but good practice)
    protected $fillable = ['user_email', 'project_code', 'sub_project', 'activity_code'];
}
