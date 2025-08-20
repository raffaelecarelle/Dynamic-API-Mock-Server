<?php

/**
 * Simple test script for the Dynamic API Mock Server web interface
 *
 * This script tests the basic functionality of the web interface
 * Run with: php tests/web_interface_test.php
 */

// Configuration
$baseUrl = 'http://localhost:8080';

// Colors for console output
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m"
];

// Helper functions
function makeRequest($url)
{
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET'
    ]);

    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    return [
        'status_code' => $statusCode,
        'body' => $response
    ];
}

function logTest($name, $success, $message = '')
{
    global $colors;

    $status = $success ? $colors['green'] . 'PASS' : $colors['red'] . 'FAIL';
    echo $colors['blue'] . "[$name] " . $status . $colors['reset'] . " $message\n";

    return $success;
}

// Run tests
echo $colors['yellow'] . "Starting web interface tests for Dynamic API Mock Server\n" . $colors['reset'];
echo "Base URL: $baseUrl\n\n";

$allTestsPassed = true;

// Test 1: Dashboard page
echo $colors['yellow'] . "Test 1: Dashboard page\n" . $colors['reset'];
$response = makeRequest("$baseUrl/dashboard");
$testResult = logTest(
    'Dashboard Page',
    $response['status_code'] === 200 && strpos($response['body'], 'Dashboard') !== false,
    "Status: {$response['status_code']}"
);
$allTestsPassed = $allTestsPassed && $testResult;

// Test 2: Projects page
echo "\n" . $colors['yellow'] . "Test 2: Projects page\n" . $colors['reset'];
$response = makeRequest("$baseUrl/dashboard/projects");
$testResult = logTest(
    'Projects Page',
    $response['status_code'] === 200 && strpos($response['body'], 'Projects') !== false,
    "Status: {$response['status_code']}"
);
$allTestsPassed = $allTestsPassed && $testResult;

// Test 3: Mocks page
echo "\n" . $colors['yellow'] . "Test 3: Mocks page\n" . $colors['reset'];
$response = makeRequest("$baseUrl/dashboard/mocks");
$testResult = logTest(
    'Mocks Page',
    $response['status_code'] === 200 && strpos($response['body'], 'Mock Endpoints') !== false,
    "Status: {$response['status_code']}"
);
$allTestsPassed = $allTestsPassed && $testResult;

// Test 4: Documentation page
echo "\n" . $colors['yellow'] . "Test 4: Documentation page\n" . $colors['reset'];
$response = makeRequest("$baseUrl/dashboard/documentation");
$testResult = logTest(
    'Documentation Page',
    $response['status_code'] === 200 && strpos($response['body'], 'API Documentation') !== false,
    "Status: {$response['status_code']}"
);
$allTestsPassed = $allTestsPassed && $testResult;

// Test 5: API Projects endpoint
echo "\n" . $colors['yellow'] . "Test 5: API Projects endpoint\n" . $colors['reset'];
$response = makeRequest("$baseUrl/api/projects");
$testResult = logTest(
    'API Projects Endpoint',
    $response['status_code'] === 200 && strpos($response['body'], '"status":"success"') !== false,
    "Status: {$response['status_code']}"
);
$allTestsPassed = $allTestsPassed && $testResult;

// Test 6: API Mocks endpoint
echo "\n" . $colors['yellow'] . "Test 6: API Mocks endpoint\n" . $colors['reset'];
$response = makeRequest("$baseUrl/api/mocks");
$testResult = logTest(
    'API Mocks Endpoint',
    $response['status_code'] === 200 && strpos($response['body'], '"status":"success"') !== false,
    "Status: {$response['status_code']}"
);
$allTestsPassed = $allTestsPassed && $testResult;

// Summary
echo "\n" . $colors['yellow'] . "Test Summary\n" . $colors['reset'];
if ($allTestsPassed) {
    echo $colors['green'] . "All tests passed successfully!\n" . $colors['reset'];
} else {
    echo $colors['red'] . "Some tests failed. Please check the output above for details.\n" . $colors['reset'];
}

echo "\n" . $colors['yellow'] . "Note: These tests only check if the pages load correctly.\n";
echo "For a complete test of the web interface functionality, manual testing is recommended.\n" . $colors['reset'];
