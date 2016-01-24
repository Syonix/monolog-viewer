<?php
require_once('bootstrap.php');

define('APP_ROOT', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('CONFIG_FILE', APP_PATH . '/config/config.yml');
define('PASSWD_DIR', APP_PATH . '/config/secure');
define('PASSWD_FILE', PASSWD_DIR . '/passwd');
define('VENDOR_PATH', __DIR__ . '/vendor');
define('BASE_URL', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') .
    '://' . $_SERVER['SERVER_NAME'] .
    str_replace('/index.php', '', $_SERVER['SCRIPT_NAME'])
);
define('WEB_URL', BASE_URL . '/web');

$app = new Silex\Application();
$app['template_url'] = WEB_URL;

if(is_readable(CONFIG_FILE)) {
    $app->register(new DerAlex\Silex\YamlConfigServiceProvider(CONFIG_FILE));
    $app['debug'] = ($app['config']['debug']);
    Symfony\Component\Debug\ExceptionHandler::register(!$app['debug']);
    if(in_array($app['config']['timezone'], DateTimeZone::listIdentifiers())) date_default_timezone_set($app['config']['timezone']);
}
$app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => APP_ROOT.'/views',
        'twig.options' => array('debug' => $app['debug'])
    ));
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider(), array(
        'security.firewalls' => array(
            'admin' => array(
                'pattern' => '^/logs',
                'form' => array('login_path' => '/login', 'check_path' => '/logs/login_check'),
                'users' => array(
                    'user' => array('ROLE_USER', (is_file(PASSWD_FILE) ? file_get_contents(PASSWD_FILE) : null)),
                ),
                'logout' => array('logout_path' => '/logs/logout'),
            ),
        ),
    ));
$app['security.encoder.digest'] = $app->share(function ($app) {
        return new \Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder(10);
    });

if(!is_file(PASSWD_FILE)) {
    $app->match('/', function(\Symfony\Component\HttpFoundation\Request $request) use($app) {
            $error = "";
            if($request->getMethod() == "POST") {
                if($request->get('password') == $request->get('password-repeat')) {
                    if(is_writable(PASSWD_DIR)) {
                        $user = new \Symfony\Component\Security\Core\User\User('user', array());
                        $encoder = $app['security.encoder_factory']->getEncoder($user);
                        $password = $encoder->encodePassword($request->get('password'), '');

                        file_put_contents(PASSWD_FILE, $password);

                        return $app['twig']->render('login.html.twig', array(
                                'create_success' => true,
                                'error' => false
                            ));
                    } else {
                        $error = 'Could not save the password. Please make sure your server can write the directory (<code>/app/config/secure/</code>).';
                    }
                } else {
                    $error = 'The provided Passwords do not match.';
                }
            }
            return $app['twig']->render('set_pwd.html.twig', array(
                    'error' => $error
                ));
        })
        ->bind("home")
        ->method('POST|GET');
    $app->match('/{url}', function(\Symfony\Component\HttpFoundation\Request $request) use($app) {
        return $app->redirect($app['url_generator']->generate('home'));
    })
    ->assert('url', '.+'); // Match any route;
}
else
{
    $app->get('/', function() use($app) {
            if(!is_readable(CONFIG_FILE)) {
                throw new \Syonix\LogViewer\Exceptions\ConfigFileMissingException();
            }
            return $app->redirect($app['url_generator']->generate('logs'));
        })
        ->bind("home");

    $app->get('/login', function(\Symfony\Component\HttpFoundation\Request $request) use($app) {
            return $app['twig']->render('login.html.twig', array(
                    'create_success' => false,
                    'error'         => $app['security.last_error']($request),
                ));
        })
        ->bind("login");

    $app->mount('/api', include 'api.php');

    $app->get('/logs{path}', function() use($app) { return $app['twig']->render('log.html.twig', array(
        'reverse_order' => $app['config']['reverse_line_order']
    )); })
        ->bind("logs")
        ->value('path', FALSE)
        ->assert("path", ".*");
}

$app->error(function (\Syonix\LogViewer\Exceptions\ConfigFileMissingException $e, $code) use($app) {
    return $app['twig']->render('error/config_file_missing.html.twig');
});

$app->error(function (\Syonix\LogViewer\Exceptions\NoLogsConfiguredException $e, $code) use($app) {
    return $app['twig']->render('error/no_log_files.html.twig');
});

$app->error(function (\Symfony\Component\HttpKernel\Exception\HttpException $e) use($app) {
    if ($app['debug']) {
        return;
    }

    switch($e->getStatusCode()) {
        case 404:
            $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);

            return $app['twig']->render('error/log_file_not_found.html.twig', array(
                'clients' => $viewer->getClients(),
                'current_client_slug' => null,
                'current_log_slug' => null,
                'error' => $e
            ));
        default:
            try {
                $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
                $clients = $viewer->getClients();
            } catch(\Exception $e) {
                $clients = array();
            }
            return $app['twig']->render('error/error.html.twig', array(
                'clients' => $clients,
                'clientSlug' => null,
                'logSlug' => null,
                'message' => 'Something went wrong!',
                'icon' => 'bug',
                'error' => $e
            ));
    }
});

$app->error(function (\Exception $e, $code) use($app) {
    if ($app['debug']) {
        return;
    }

    switch($code) {
        default:
            try {
                $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
                $clients = $viewer->getClients();
            } catch(\Exception $e) {
                $clients = array();
            }
            return $app['twig']->render('error/error.html.twig', array(
                'clients' => $clients,
                'clientSlug' => null,
                'logSlug' => null,
                'message' => 'Something went wrong!',
                'icon' => 'bug',
                'error' => $e
            ));
    }
});

$app->run();
