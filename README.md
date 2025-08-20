# Dynamic API Mock Server

A PHP server that allows creating, saving, and sharing dynamic REST API mocks through a web interface and JSON.

## Features

- Create mock endpoints for any HTTP method (GET, POST, PUT, DELETE, PATCH)
- Organize endpoints into projects
- Configure response status codes, headers, and body
- Create dynamic responses with randomized data
- Simulate network delays
- Share mock endpoints with others
- Import and export projects

## Requirements

- PHP >= 8.1
- Composer
- Web server (Apache/Nginx) or PHP built-in server
- SQLite/MySQL/PostgreSQL

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/raffaelecarelle/dynamic-api-mock-server.git
   cd dynamic-api-mock-server
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure the environment:
   ```bash
   cp .env.dist .env
   ```
   Edit the `.env` file to set your database connection and other settings.

4. Start the server:
   ```bash
   # Using PHP built-in server
   php -S localhost:8080 -t public
   
   # Or configure your Apache/Nginx to point to the public directory
   ```

5. Access the dashboard:
   ```
   http://localhost:8080/dashboard
   ```

## Usage

### Creating a Project

1. Go to the Projects page
2. Click "Create Project"
3. Enter a name and optional description
4. Choose whether the project should be public or private
5. Click "Create Project"

### Creating a Mock Endpoint

1. Go to the Mock Endpoints page
2. Click "Create Mock Endpoint"
3. Select your project
4. Configure the endpoint (method, path, status code, etc.)
5. Define the response body and headers
6. Click "Create Mock Endpoint"

### Using a Mock Endpoint

Your mock endpoint is now available at:
```
http://localhost:8080/mock/{project-name}/{endpoint-path}
```

For example, if your project is named "users-api" and your endpoint path is "/api/users", the URL would be:
```
http://localhost:8080/mock/users-api/api/users
```

### Dynamic Responses

You can create dynamic responses by enabling the "Dynamic Response" option when creating or editing a mock endpoint. This allows you to:

- Generate random numbers, strings, booleans, and dates
- Use request parameters in the response
- Apply conditional logic based on request parameters

Dynamic rules are defined as an array of objects, each with a specific type and target:

#### Random Number
```json
{
  "type": "random_number",
  "target": "data.id",
  "min": 1,
  "max": 1000
}
```
Generates a random number between `min` and `max` and sets it at the specified `target` path in the response body.

#### Random String
```json
{
  "type": "random_string",
  "target": "data.token",
  "length": 16,
  "characters": "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
}
```
Generates a random string of the specified `length` using the provided `characters`.

#### Random Boolean
```json
{
  "type": "random_boolean",
  "target": "data.active"
}
```
Generates a random boolean value (true or false).

#### Random Date
```json
{
  "type": "random_date",
  "target": "data.created_at",
  "format": "Y-m-d H:i:s",
  "min_days": -30,
  "max_days": 30
}
```
Generates a random date between `min_days` and `max_days` from the current date, formatted according to `format`.

#### Request Parameter
```json
{
  "type": "request_param",
  "target": "data.user_id",
  "param_type": "path",
  "param_name": "id"
}
```
Uses a parameter from the request in the response. `param_type` can be "path", "query", or "body".

#### Conditional
```json
{
  "type": "conditional",
  "target": "message",
  "condition": {
    "param_type": "query",
    "param_name": "status",
    "operator": "equals",
    "value": "active"
  },
  "then": "User is active",
  "else": "User is inactive"
}
```
Sets different values based on a condition. Operators include "equals", "not_equals", "greater_than", "less_than", and "contains".

### Sharing Mock Endpoints

1. Go to the Projects page
2. Click the share button for the project you want to share
3. Copy the share links

Shared mock endpoints can be accessed using:
```
http://localhost:8080/share/{token}/{endpoint-path}
```

## API Reference

The server provides a RESTful API for managing projects and mock endpoints:

### Projects API

- `GET /api/projects` - List all projects
- `GET /api/projects/{id}` - Get a project
- `POST /api/projects` - Create a project
- `PUT /api/projects/{id}` - Update a project
- `DELETE /api/projects/{id}` - Delete a project
- `POST /api/projects/{id}/export` - Export a project
- `POST /api/projects/import` - Import a project
- `GET /api/share/{token}` - Get a project by share token

### Mock Endpoints API

- `GET /api/mocks` - List all mock endpoints
- `GET /api/mocks?project_id={id}` - List mock endpoints for a project
- `GET /api/mocks/{id}` - Get a mock endpoint
- `POST /api/mocks` - Create a mock endpoint
- `PUT /api/mocks/{id}` - Update a mock endpoint
- `DELETE /api/mocks/{id}` - Delete a mock endpoint

## Testing

The project includes a test script to verify the API functionality:

```bash
# Start the server
php -S localhost:8080 -t public

# In another terminal, run the tests
php tests/api_test.php
```

The test script will:
1. Create a test project
2. Create a mock endpoint
3. Test the mock endpoint
4. Update the mock endpoint
5. Test the updated mock endpoint
6. Export the project
7. Test the shared mock endpoint
8. Delete the mock endpoint and project
9. Import the project
10. Clean up

## License

MIT

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.