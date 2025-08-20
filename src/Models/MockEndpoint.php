<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MockEndpoint extends Model
{
    protected $fillable = [
        'project_id',
        'method',
        'path',
        'status_code',
        'headers',
        'response_body',
        'delay',
        'is_dynamic',
        'dynamic_rules',
        'description'
    ];

    protected $casts = [
        'headers' => 'json',
        'response_body' => 'json',
        'dynamic_rules' => 'json',
        'is_dynamic' => 'boolean',
        'delay' => 'integer',
        'status_code' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the project that owns the mock endpoint.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if the mock endpoint matches the given request path
     *
     * @param string $requestPath
     * @return bool
     */
    public function matchesPath(string $requestPath): bool
    {
        // If the path is exact, just compare directly
        if (strpos($this->path, '{') === false) {
            return $this->path === $requestPath;
        }

        // Convert path with parameters to regex pattern
        $pattern = preg_replace('/\{([^\/]+)\}/', '([^/]+)', $this->path);
        $pattern = '@^' . $pattern . '$@';

        return (bool) preg_match($pattern, $requestPath);
    }

    /**
     * Extract path parameters from the request path
     *
     * @param string $requestPath
     * @return array
     */
    public function extractPathParams(string $requestPath): array
    {
        $params = [];

        // If no parameters in path, return empty array
        if (strpos($this->path, '{') === false) {
            return $params;
        }

        // Extract parameter names from path
        preg_match_all('/\{([^\/]+)\}/', $this->path, $paramNames);

        // Convert path with parameters to regex pattern with capture groups
        $pattern = preg_replace('/\{([^\/]+)\}/', '([^/]+)', $this->path);
        $pattern = '@^' . $pattern . '$@';

        // Extract parameter values from request path
        preg_match($pattern, $requestPath, $paramValues);

        // Skip the first match (full string)
        array_shift($paramValues);

        // Combine parameter names with values
        foreach ($paramNames[1] as $index => $name) {
            if (isset($paramValues[$index])) {
                $params[$name] = $paramValues[$index];
            }
        }

        return $params;
    }
}
