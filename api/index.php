<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/public/index.php';

$env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'prod';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? false);

// Database fallback for serverless when no remote DB is configured
$dbUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? '';
$isLocalDb = ($dbUrl === '' || str_contains($dbUrl, '127.0.0.1') || str_contains($dbUrl, 'localhost'));
$sqlitePath = sys_get_temp_dir() . '/app_senior_platform.db';

if ($isLocalDb) {
    $fallbackUrl = 'sqlite:///' . $sqlitePath;
    $_SERVER['DATABASE_URL'] = $fallbackUrl;
    $_ENV['DATABASE_URL'] = $fallbackUrl;
    putenv('DATABASE_URL=' . $fallbackUrl);
}

$kernel = new Kernel($env, $debug);
$kernel->boot();

// If using SQLite fallback, ensure schema exists
if ($isLocalDb && (!file_exists($sqlitePath) || filesize($sqlitePath) === 0)) {
    try {
        $em = $kernel->getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        $classes = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema($classes);
        $em->getRepository(\App\Entity\User::class)->findOrCreateDefaultUser();
    } catch (\Throwable $e) {
        // Silently continue if schema already initialized
    }
}

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);