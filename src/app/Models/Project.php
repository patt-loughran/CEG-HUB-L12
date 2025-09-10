<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Project extends Model
{
      // Explicitly set the connection if it's not your default
    protected $connection = 'mongodb';

    // Set the collection name
    protected $table = 'projects';

    // Define which fields can be mass-assigned (optional but good practice)
    protected $fillable = ['projectname', 'projectcode', 'datentp', 'dateenergization', 'dollarvalueinhouse', 'clientcompany', 'clientcontactname', 'clientcontactemail', 'projectmanager', 'dateproposed', 'country', 'state', 'utility', 'voltage', 'mwsize', 'projectstatus', 'billingmethod', 'projecttype', 'sub-projects', 'updated_at', 'created_at', 'expense', 'hours_data', 'is_internal', 'ongoing', 'earliest_entry'];
}
