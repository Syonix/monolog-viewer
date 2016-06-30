<?php

require_once '../bootstrap.php';

define('APP_ROOT', realpath(__DIR__.'/../'));
define('APP_PATH', APP_ROOT.'/app');
define('CONFIG_FILE', APP_PATH.'/config/config.yml');
define('PASSWD_DIR', APP_PATH.'/config/secure');
define('PASSWD_FILE', PASSWD_DIR.'/passwd');
define('VENDOR_PATH', APP_ROOT.'/vendor');
define('BASE_URL', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http').
    '://'.$_SERVER['SERVER_NAME'].
    str_replace('/index.php', '', $_SERVER['SCRIPT_NAME'])
);

$app = new Silex\Application();
$app['template_url'] = BASE_URL;

if (is_readable(CONFIG_FILE)) {
    $app->register(new DerAlex\Silex\YamlConfigServiceProvider(CONFIG_FILE));
    $app['debug'] = ($app['config']['debug']);
    Symfony\Component\Debug\ExceptionHandler::register(!$app['debug']);
    if (in_array($app['config']['timezone'], DateTimeZone::listIdentifiers())) {
        date_default_timezone_set($app['config']['timezone']);
    }
}
$app->register(new Silex\Provider\TwigServiceProvider(), [
        'twig.path'    => APP_ROOT.'/views',
        'twig.options' => ['debug' => $app['debug']],
    ]);
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider(), [
        'security.firewalls' => [
            'admin' => [
                'pattern' => '^/(logs|api)',
                'form'    => ['login_path' => '/login', 'check_path' => '/logs/login_check'],
                'users'   => [
                    'user' => ['ROLE_USER', (is_file(PASSWD_FILE) ? file_get_contents(PASSWD_FILE) : null)],
                ],
                'logout' => ['logout_path' => '/logs/logout', 'invalidate_session' => true],
            ],
        ],
    ]);
$app['security.encoder.digest'] = $app->share(function ($app) {
        return new \Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder(10);
    });
if(!is_file(PASSWD_FILE)) {
    $app->mount('/', include 'guest.php');
}
else {
    $app->mount('/api', include 'api.php');
    $app->mount('/', include 'user.php');
}

$app->error(function (\Syonix\LogViewer\Exceptions\ConfigFileMissingException $e, $code) use ($app) {
    return $app['twig']->render('error/config_file_missing.html.twig');
});

$app->error(function (\Syonix\LogViewer\Exceptions\NoLogsConfiguredException $e, $code) use ($app) {
    return $app['twig']->render('error/no_log_files.html.twig');
});

$app->error(function (\Symfony\Component\HttpKernel\Exception\HttpException $e) use ($app) {
    if ($app['debug']) {
        return;
    }

    switch ($e->getStatusCode()) {
        case 404:
            $viewer = new Syonix\LogViewer\LogManager($app['config']['logs']);

            return $app['twig']->render('error/log_file_not_found.html.twig', [
                'clients'             => $viewer->getLogCollections(),
                'current_client_slug' => null,
                'current_log_slug'    => null,
                'error'               => $e,
            ]);
        default:
            try {
                $viewer = new Syonix\LogViewer\LogManager($app['config']['logs']);
                $clients = $viewer->getLogCollections();
            } catch (\Exception $e) {
                $clients = [];
            }

            return $app['twig']->render('error/error.html.twig', [
                'clients'    => $clients,
                'clientSlug' => null,
                'logSlug'    => null,
                'message'    => 'Something went wrong!',
                'icon'       => 'bug',
                'error'      => $e,
            ]);
    }
});

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    switch ($code) {
        default:
            try {
                $viewer = new Syonix\LogViewer\LogManager($app['config']['logs']);
                $clients = $viewer->getLogCollections();
            } catch (\Exception $e) {
                $clients = [];
            }

            return $app['twig']->render('error/error.html.twig', [
                'clients'    => $clients,
                'clientSlug' => null,
                'logSlug'    => null,
                'message'    => 'Something went wrong!',
                'icon'       => 'bug',
                'error'      => $e,
            ]);
    }
});

$app->run();
