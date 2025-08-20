<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_public',
        'share_token'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($project) {
            // Generate a UUID for the share token if not provided
            if (empty($project->share_token)) {
                $project->share_token = Uuid::uuid4()->toString();
            }
        });
    }

    /**
     * Get the mocks for the project.
     */
    public function mocks()
    {
        return $this->hasMany(MockEndpoint::class);
    }

    /**
     * Export project with all its mocks
     *
     * @return array
     */
    public function export()
    {
        return [
            'project' => $this->toArray(),
            'mocks' => $this->mocks()->get()->toArray()
        ];
    }
}
