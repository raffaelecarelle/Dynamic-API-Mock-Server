<?php

use App\Middleware\SharedMockRequestHandler;
use App\Services\MockService;
use App\Services\ProjectService;
use App\Services\ResponseGeneratorService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    // Controllers
    \App\Controllers\DashboardController::class => function (ContainerInterface $container) {
        return new \App\Controllers\DashboardController(
            $container->get(ProjectService::class),
            $container->get(MockService::class),
            $container->get(LoggerInterface::class)
        );
    },
    
    // Database
    Capsule::class => function () {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => $_ENV['DB_DRIVER'] ?? 'sqlite',
            'host'      => $_ENV['DB_HOST'] ?? 'localhost',
            'database'  => $_ENV['DB_DATABASE'] ?? __DIR__ . '/../storage/database.sqlite',
            'username'  => $_ENV['DB_USERNAME'] ?? '',
            'password'  => $_ENV['DB_PASSWORD'] ?? '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    },
    
    // Logger
    LoggerInterface::class => function () {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler(
            __DIR__ . '/../logs/app.log',
            $_ENV['LOG_LEVEL'] ?? Logger::DEBUG
        ));
        return $logger;
    },
    
    // Services
    MockService::class => function (ContainerInterface $container) {
        return new MockService(
            $container->get(LoggerInterface::class),
            $container->get(ResponseGeneratorService::class),
            $container->get(Capsule::class)->table('mock_endpoints')
        );
    },
    
    ProjectService::class => function (ContainerInterface $container) {
        return new ProjectService(
            $container->get(LoggerInterface::class),
            $container->get(Capsule::class)->table('projects')
        );
    },
    
    ResponseGeneratorService::class => function (ContainerInterface $container) {
        return new ResponseGeneratorService(
            $container->get(LoggerInterface::class)
        );
    },
    
    SharedMockRequestHandler::class => function (ContainerInterface $container) {
        return new SharedMockRequestHandler(
            $container->get(ProjectService::class),
            $container->get(MockService::class),
            $container->get(LoggerInterface::class)
        );
    }
];