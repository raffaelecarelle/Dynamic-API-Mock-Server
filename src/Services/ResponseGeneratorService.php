<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

class ResponseGeneratorService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ResponseGeneratorService constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Generate a response based on the mock endpoint configuration
     *
     * @param array $mockData The mock endpoint data
     * @param array $requestData Request data (path params, query params, body)
     * @return array The generated response with body, headers, and status code
     */
    public function generateResponse(array $mockData, array $requestData): array
    {
        $responseBody = $mockData['response_body'] ?? [];
        $headers = $mockData['headers'] ?? [];
        $statusCode = $mockData['status_code'] ?? 200;

        // If the mock is not dynamic, return the static response
        if (!($mockData['is_dynamic'] ?? false)) {
            return [
                'body' => $responseBody,
                'headers' => $headers,
                'status_code' => $statusCode
            ];
        }

        // Process dynamic response
        $dynamicRules = $mockData['dynamic_rules'] ?? [];

        // Apply dynamic rules to the response body
        $responseBody = $this->applyDynamicRules($responseBody, $dynamicRules, $requestData);

        return [
            'body' => $responseBody,
            'headers' => $headers,
            'status_code' => $statusCode
        ];
    }

    /**
     * Apply dynamic rules to the response body
     *
     * @param array $responseBody The original response body
     * @param array $rules The dynamic rules to apply
     * @param array $requestData Request data (path params, query params, body)
     * @return array The modified response body
     */
    private function applyDynamicRules(array $responseBody, array $rules, array $requestData): array
    {
        if (empty($rules)) {
            return $responseBody;
        }

        $this->logger->debug('Applying dynamic rules', ['rules' => $rules]);

        foreach ($rules as $rule) {
            $type = $rule['type'] ?? '';
            $target = $rule['target'] ?? '';

            if (empty($type) || empty($target)) {
                continue;
            }

            switch ($type) {
                case 'random_number':
                    $responseBody = $this->applyRandomNumber($responseBody, $target, $rule);
                    break;

                case 'random_string':
                    $responseBody = $this->applyRandomString($responseBody, $target, $rule);
                    break;

                case 'random_boolean':
                    $responseBody = $this->applyRandomBoolean($responseBody, $target, $rule);
                    break;

                case 'random_date':
                    $responseBody = $this->applyRandomDate($responseBody, $target, $rule);
                    break;

                case 'request_param':
                    $responseBody = $this->applyRequestParam($responseBody, $target, $rule, $requestData);
                    break;

                case 'conditional':
                    $responseBody = $this->applyConditional($responseBody, $target, $rule, $requestData);
                    break;
            }
        }

        return $responseBody;
    }

    /**
     * Apply a random number to the target path in the response body
     */
    private function applyRandomNumber(array $responseBody, string $target, array $rule): array
    {
        $min = $rule['min'] ?? 0;
        $max = $rule['max'] ?? 100;
        $value = rand($min, $max);

        return $this->setValueAtPath($responseBody, $target, $value);
    }

    /**
     * Apply a random string to the target path in the response body
     */
    private function applyRandomString(array $responseBody, string $target, array $rule): array
    {
        $length = $rule['length'] ?? 10;
        $characters = $rule['characters'] ?? 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $value = '';

        for ($i = 0; $i < $length; $i++) {
            $value .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $this->setValueAtPath($responseBody, $target, $value);
    }

    /**
     * Apply a random boolean to the target path in the response body
     */
    private function applyRandomBoolean(array $responseBody, string $target, array $rule): array
    {
        $value = (bool) rand(0, 1);
        return $this->setValueAtPath($responseBody, $target, $value);
    }

    /**
     * Apply a random date to the target path in the response body
     */
    private function applyRandomDate(array $responseBody, string $target, array $rule): array
    {
        $format = $rule['format'] ?? 'Y-m-d';
        $minDays = $rule['min_days'] ?? -30;
        $maxDays = $rule['max_days'] ?? 30;

        $timestamp = time() + rand($minDays * 86400, $maxDays * 86400);
        $value = date($format, $timestamp);

        return $this->setValueAtPath($responseBody, $target, $value);
    }

    /**
     * Apply a request parameter to the target path in the response body
     */
    private function applyRequestParam(array $responseBody, string $target, array $rule, array $requestData): array
    {
        $paramType = $rule['param_type'] ?? 'path';
        $paramName = $rule['param_name'] ?? '';

        if (empty($paramName)) {
            return $responseBody;
        }

        $value = null;

        switch ($paramType) {
            case 'path':
                $value = $requestData['path_params'][$paramName] ?? null;
                break;

            case 'query':
                $value = $requestData['query_params'][$paramName] ?? null;
                break;

            case 'body':
                $value = $this->getValueFromPath($requestData['body'] ?? [], $paramName);
                break;
        }

        if ($value !== null) {
            return $this->setValueAtPath($responseBody, $target, $value);
        }

        return $responseBody;
    }

    /**
     * Apply a conditional rule to the target path in the response body
     */
    private function applyConditional(array $responseBody, string $target, array $rule, array $requestData): array
    {
        $condition = $rule['condition'] ?? [];
        $thenValue = $rule['then'] ?? null;
        $elseValue = $rule['else'] ?? null;

        if (empty($condition) || $thenValue === null) {
            return $responseBody;
        }

        $conditionMet = $this->evaluateCondition($condition, $requestData);
        $value = $conditionMet ? $thenValue : $elseValue;

        if ($value !== null) {
            return $this->setValueAtPath($responseBody, $target, $value);
        }

        return $responseBody;
    }

    /**
     * Evaluate a condition against request data
     */
    private function evaluateCondition(array $condition, array $requestData): bool
    {
        $paramType = $condition['param_type'] ?? 'path';
        $paramName = $condition['param_name'] ?? '';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        if (empty($paramName) || $value === null) {
            return false;
        }

        $paramValue = null;

        switch ($paramType) {
            case 'path':
                $paramValue = $requestData['path_params'][$paramName] ?? null;
                break;

            case 'query':
                $paramValue = $requestData['query_params'][$paramName] ?? null;
                break;

            case 'body':
                $paramValue = $this->getValueFromPath($requestData['body'] ?? [], $paramName);
                break;
        }

        if ($paramValue === null) {
            return false;
        }

        switch ($operator) {
            case 'equals':
                return $paramValue == $value;

            case 'not_equals':
                return $paramValue != $value;

            case 'greater_than':
                return $paramValue > $value;

            case 'less_than':
                return $paramValue < $value;

            case 'contains':
                return is_string($paramValue) && strpos($paramValue, $value) !== false;

            default:
                return false;
        }
    }

    /**
     * Set a value at a specific path in an array
     *
     * @param array $array The array to modify
     * @param string $path The path to set (dot notation)
     * @param mixed $value The value to set
     * @return array The modified array
     */
    private function setValueAtPath(array $array, string $path, $value): array
    {
        $keys = explode('.', $path);
        $current = &$array;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }

        return $array;
    }

    /**
     * Get a value from a specific path in an array
     *
     * @param array $array The array to get the value from
     * @param string $path The path to get (dot notation)
     * @return mixed|null The value or null if not found
     */
    private function getValueFromPath(array $array, string $path)
    {
        $keys = explode('.', $path);
        $current = $array;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }
}
