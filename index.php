<?php
require_once('bootstrap.php');

define('APP_ROOT', __DIR__);
define('APP_PATH', __DIR__ . '/app/');
define('CONFIG_FILE', APP_PATH . 'config/config.json');
define('PASSWD_DIR', APP_PATH . 'config/secure');
define('PASSWD_FILE', PASSWD_DIR . '/passwd');
define('VENDOR_PATH', __DIR__ . '/vendor/');
define('BASE_URL', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
define('WEB_URL', BASE_URL . 'web/');

$app = new Silex\Application();

$app->register(new DerAlex\Silex\YamlConfigServiceProvider('app/config/config.yml'));
$app['debug'] = ($app['config']['debug']);

if(in_array($app['config']['timezone'], DateTimeZone::listIdentifiers())) date_default_timezone_set($app['config']['timezone']);

$app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/views',
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
                    'user' => array('ROLE_USER', file_get_contents(PASSWD_FILE)),
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
        ->bind("set_pwd")
        ->method('POST|GET');
    $app->match('/{url}', function(\Symfony\Component\HttpFoundation\Request $request) use($app) {
        return $app->redirect($app['url_generator']->generate('set_pwd'));
    })
    ->assert('url', '.+'); // Match any route;
}
else
{

    $app->get('/', function() use($app) {
            return $app->redirect($app['url_generator']->generate('home'));
        });

    $app->get('/login', function(\Symfony\Component\HttpFoundation\Request $request) use($app) {
            return $app['twig']->render('login.html.twig', array(
                    'create_success' => false,
                    'error'         => $app['security.last_error']($request),
                ));
        })
        ->bind("login");

    $app->get('/logs', function() use($app) {
            $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);

            $clientSlug = $viewer->getFirstClient();
            $logSlug  = $viewer->getFirstLog($clientSlug);

            return $app->redirect($app['url_generator']->generate('log', array(
                        'clientSlug' => $clientSlug,
                        'logSlug' => $logSlug
                    )));
        })
        ->bind("home");

    $app->get('/logs/{clientSlug}', function($clientSlug) use($app) {
            $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);

            $logSlug = $viewer->getFirstLog($clientSlug);
            if(is_null($logSlug))
            {
                $app->abort(404, "Client not found");
            }

            return $app->redirect($app['url_generator']->generate('log', array(
                        'clientSlug' => $clientSlug,
                        'logSlug' => $logSlug
                    )));
        })
        ->bind("client");

    $app->get('/logs/{clientSlug}/{logSlug}', function($clientSlug, $logSlug) use($app) {
            try {
                $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
                $clients = $viewer->getClients();
                $logs = $viewer->getLogs($clientSlug);
                $log = $viewer->getLog($clientSlug, $logSlug);
            } catch(\League\Flysystem\FileNotFoundException $e) {
                return $app['twig']->render('error/log_file_not_found.html.twig', array(
                    'clients' => $viewer->getClients(),
                    'logs' => $viewer->getLogs($clientSlug),
                    'clientSlug' => $clientSlug,
                    'logSlug' => $logSlug,
                    'error' => $e
                ));
            } catch(InvalidArgumentException $e) {
                return $app['twig']->render('error/exception.html.twig', array(
                    'clients' => $viewer->getClients(),
                    'logs' => $viewer->getLogs($clientSlug),
                    'clientSlug' => $clientSlug,
                    'logSlug' => $logSlug,
                    'icon' => 'bug',
                    'message' => $e->getMessage(),
                    'error' => $e
                ));
            } catch(\Exception $e) {
                return $app['twig']->render('error/error.html.twig', array(
                    'clients' => (isset($clients) && count($clients) > 0) ? $clients : null,
                    'logs' => (isset($logs) && count($logs) > 0) ? $logs : null,
                    'message' => 'Something went wrong!',
                    'icon' => 'bug',
                    'error' => $e
                ));
            }
            return $app['twig']->render('log.html.twig', array(
                    'clients' => $clients,
                    'logs' => $logs,
                    'clientSlug' => $clientSlug,
                    'logSlug' => $logSlug,
                    'log' => $log,
                    'logLevels' => Monolog\Logger::getLevels(),
                    'minLogLevel' => 0
                ));
        })
        ->bind("log");

    $app->get('/logs/{clientSlug}/{logSlug}/{minLogLevel}', function($clientSlug, $logSlug, $minLogLevel) use($app) {
            $minLogLevel = intval($minLogLevel);
            $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
            if($viewer->hasLogs() === false) throw new Exception("No logs were found. Please check your config file.");

            $log = $viewer->getLog($clientSlug, $logSlug);

            return $app['twig']->render('log.html.twig', array(
                    'clients' => $viewer->getClients(),
                    'logs' => $viewer->getLogs($clientSlug),
                    'clientSlug' => $clientSlug,
                    'logSlug' => $logSlug,
                    'log' => $log,
                    'logLevels' => Monolog\Logger::getLevels(),
                    'minLogLevel' => (in_array($minLogLevel, Monolog\Logger::getLevels()) ? $minLogLevel : 0)
                ));
        })
        ->assert('minLogLevel', '\d+')
        ->bind("minLogLevel");
}

$app->error(function (\League\Flysystem\FileNotFoundException $e, $code) use($app) {
    $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);

    return $app['twig']->render('error/404.html.twig', array(
        'clients' => $viewer->getClients(),
        'logs' => array(),
        'message' => 'The log file could not be found!',
        'icon' => 'search',
        'error' => false
    ));
});

$app->error(function (\Exception $e, $code) use($app) {
    if ($app['debug']) {
        return;
    }

    switch($code) {
        case 404:
            $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);

            return $app['twig']->render('error/404.html.twig', array(
                'clients' => $viewer->getClients(),
                'message' => 'The page you are looking for does not exist!',
                'icon' => 'search',
                'error' => false
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
                    'message' => 'Something went wrong!',
                    'icon' => 'bug',
                    'error' => $e
                ));
    }
});

$app->run();
