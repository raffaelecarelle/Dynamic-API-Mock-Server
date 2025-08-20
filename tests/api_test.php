<?php

/**
 * Simple test script for the Dynamic API Mock Server
 *
 * This script tests the basic functionality of the API endpoints
 * Run with: php tests/api_test.php
 */

// Configuration
$baseUrl = 'http://localhost:8080';
$testProject = [
    'name' => 'Test Project ' . time(),
    'description' => 'A test project created by the API test script',
    'is_public' => true
];
$testMock = [
    'method' => 'GET',
    'path' => '/api/test',
    'status_code' => 200,
    'headers' => ['Content-Type' => 'application/json'],
    'response_body' => ['message' => 'Test successful'],
    'delay' => 0,
    'is_dynamic' => false,
    'description' => 'A test mock endpoint'
];

// Colors for console output
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m"
];

// Helper functions
function makeRequest($method, $url, $data = null)
{
    $curl = curl_init();

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method
    ];

    if ($data !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
        $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    return [
        'status_code' => $statusCode,
        'body' => json_decode($response, true)
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
echo $colors['yellow'] . "Starting API tests for Dynamic API Mock Server\n" . $colors['reset'];
echo "Base URL: $baseUrl\n\n";

$allTestsPassed = true;

// Test 1: Create a project
echo $colors['yellow'] . "Test 1: Create a project\n" . $colors['reset'];
$response = makeRequest('POST', "$baseUrl/api/projects", $testProject);
$testResult = logTest(
    'Create Project',
    $response['status_code'] === 201 && $response['body']['status'] === 'success',
    "Status: {$response['status_code']}"
);
$allTestsPassed = $allTestsPassed && $testResult;

if ($testResult) {
    $projectId = $response['body']['data']['id'];
    $projectName = $response['body']['data']['name'];
    $shareToken = $response['body']['data']['share_token'];
    echo "Created project with ID: $projectId, Name: $projectName\n\n";

    // Test 2: Get the project
    echo $colors['yellow'] . "Test 2: Get the project\n" . $colors['reset'];
    $response = makeRequest('GET', "$baseUrl/api/projects/$projectId");
    $testResult = logTest(
        'Get Project',
        $response['status_code'] === 200 && $response['body']['status'] === 'success' && $response['body']['data']['id'] === $projectId,
        "Status: {$response['status_code']}"
    );
    $allTestsPassed = $allTestsPassed && $testResult;

    // Test 3: Create a mock endpoint
    echo "\n" . $colors['yellow'] . "Test 3: Create a mock endpoint\n" . $colors['reset'];
    $testMock['project_id'] = $projectId;
    $response = makeRequest('POST', "$baseUrl/api/mocks", $testMock);
    $testResult = logTest(
        'Create Mock',
        $response['status_code'] === 201 && $response['body']['status'] === 'success',
        "Status: {$response['status_code']}"
    );
    $allTestsPassed = $allTestsPassed && $testResult;

    if ($testResult) {
        $mockId = $response['body']['data']['id'];
        echo "Created mock endpoint with ID: $mockId\n\n";

        // Test 4: Get the mock endpoint
        echo $colors['yellow'] . "Test 4: Get the mock endpoint\n" . $colors['reset'];
        $response = makeRequest('GET', "$baseUrl/api/mocks/$mockId");
        $testResult = logTest(
            'Get Mock',
            $response['status_code'] === 200 && $response['body']['status'] === 'success' && $response['body']['data']['id'] === $mockId,
            "Status: {$response['status_code']}"
        );
        $allTestsPassed = $allTestsPassed && $testResult;

        // Test 5: Test the mock endpoint
        echo "\n" . $colors['yellow'] . "Test 5: Test the mock endpoint\n" . $colors['reset'];
        $response = makeRequest('GET', "$baseUrl/mock/$projectName/api/test");
        $testResult = logTest(
            'Test Mock',
            $response['status_code'] === 200 && isset($response['body']['message']) && $response['body']['message'] === 'Test successful',
            "Status: {$response['status_code']}"
        );
        $allTestsPassed = $allTestsPassed && $testResult;

        // Test 6: Update the mock endpoint
        echo "\n" . $colors['yellow'] . "Test 6: Update the mock endpoint\n" . $colors['reset'];
        $updateMock = $testMock;
        $updateMock['response_body'] = ['message' => 'Updated test successful'];
        $response = makeRequest('PUT', "$baseUrl/api/mocks/$mockId", $updateMock);
        $testResult = logTest(
            'Update Mock',
            $response['status_code'] === 200 && $response['body']['status'] === 'success',
            "Status: {$response['status_code']}"
        );
        $allTestsPassed = $allTestsPassed && $testResult;

        // Test 7: Test the updated mock endpoint
        echo "\n" . $colors['yellow'] . "Test 7: Test the updated mock endpoint\n" . $colors['reset'];
        $response = makeRequest('GET', "$baseUrl/mock/$projectName/api/test");
        $testResult = logTest(
            'Test Updated Mock',
            $response['status_code'] === 200 && isset($response['body']['message']) && $response['body']['message'] === 'Updated test successful',
            "Status: {$response['status_code']}"
        );
        $allTestsPassed = $allTestsPassed && $testResult;

        // Test 8: Export the project
        echo "\n" . $colors['yellow'] . "Test 8: Export the project\n" . $colors['reset'];
        $response = makeRequest('POST', "$baseUrl/api/projects/$projectId/export");
        $testResult = logTest(
            'Export Project',
            $response['status_code'] === 200 && $response['body']['status'] === 'success' && isset($response['body']['data']['project']) && isset($response['body']['data']['mocks']),
            "Status: {$response['status_code']}"
        );
        $allTestsPassed = $allTestsPassed && $testResult;

        if ($testResult) {
            $exportData = $response['body']['data'];

            // Test 9: Test shared mock endpoint
            echo "\n" . $colors['yellow'] . "Test 9: Test shared mock endpoint\n" . $colors['reset'];
            $response = makeRequest('GET', "$baseUrl/share/$shareToken/api/test");
            $testResult = logTest(
                'Test Shared Mock',
                $response['status_code'] === 200 && isset($response['body']['message']) && $response['body']['message'] === 'Updated test successful',
                "Status: {$response['status_code']}"
            );
            $allTestsPassed = $allTestsPassed && $testResult;

            // Test 10: Delete the mock endpoint
            echo "\n" . $colors['yellow'] . "Test 10: Delete the mock endpoint\n" . $colors['reset'];
            $response = makeRequest('DELETE', "$baseUrl/api/mocks/$mockId");
            $testResult = logTest(
                'Delete Mock',
                $response['status_code'] === 200 && $response['body']['status'] === 'success',
                "Status: {$response['status_code']}"
            );
            $allTestsPassed = $allTestsPassed && $testResult;

            // Test 11: Delete the project
            echo "\n" . $colors['yellow'] . "Test 11: Delete the project\n" . $colors['reset'];
            $response = makeRequest('DELETE', "$baseUrl/api/projects/$projectId");
            $testResult = logTest(
                'Delete Project',
                $response['status_code'] === 200 && $response['body']['status'] === 'success',
                "Status: {$response['status_code']}"
            );
            $allTestsPassed = $allTestsPassed && $testResult;

            // Test 12: Import the project
            echo "\n" . $colors['yellow'] . "Test 12: Import the project\n" . $colors['reset'];
            // Remove IDs from export data to create a new project
            unset($exportData['project']['id']);
            $exportData['project']['name'] = 'Imported ' . $exportData['project']['name'];
            foreach ($exportData['mocks'] as &$mock) {
                unset($mock['id']);
            }

            $response = makeRequest('POST', "$baseUrl/api/projects/import", $exportData);
            $testResult = logTest(
                'Import Project',
                $response['status_code'] === 201 && $response['body']['status'] === 'success',
                "Status: {$response['status_code']}"
            );
            $allTestsPassed = $allTestsPassed && $testResult;

            if ($testResult) {
                $importedProjectId = $response['body']['data']['project']['id'];
                $importedProjectName = $response['body']['data']['project']['name'];
                echo "Imported project with ID: $importedProjectId, Name: $importedProjectName\n";

                // Clean up imported project
                $response = makeRequest('DELETE', "$baseUrl/api/projects/$importedProjectId");
                $testResult = logTest(
                    'Delete Imported Project',
                    $response['status_code'] === 200 && $response['body']['status'] === 'success',
                    "Status: {$response['status_code']}"
                );
                $allTestsPassed = $allTestsPassed && $testResult;
            }
        }
    }
}

// Summary
echo "\n" . $colors['yellow'] . "Test Summary\n" . $colors['reset'];
if ($allTestsPassed) {
    echo $colors['green'] . "All tests passed successfully!\n" . $colors['reset'];
} else {
    echo $colors['red'] . "Some tests failed. Please check the output above for details.\n" . $colors['reset'];
}
