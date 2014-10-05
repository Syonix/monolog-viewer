<?php
require_once('bootstrap.php');

define('APP_PATH', __DIR__ . '/app/');
define('CONFIG_FILE', APP_PATH . 'config/config.json');
define('PASSWD_DIR', APP_PATH . 'config/secure');
define('PASSWD_FILE', PASSWD_DIR . '/passwd');
define('VENDOR_PATH', __DIR__ . '/vendor/');
define('BASE_URL', ($_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
define('WEB_URL', BASE_URL . 'web/');

$app = new Silex\Application();

$app['mvconfig'] = json_decode(\file_get_contents(CONFIG_FILE), true);
$app['debug'] = $app['mvconfig']['debug'];

if(in_array($app['mvconfig']['timezone'], DateTimeZone::listIdentifiers())) date_default_timezone_set($app['mvconfig']['timezone']);

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
            $viewer = new \SyonixLogViewer\LogViewer(CONFIG_FILE);

            $clientSlug = $viewer->getFirstClient();
            $logSlug  = $viewer->getFirstLog($clientSlug);

            return $app->redirect($app['url_generator']->generate('log', array(
                        'clientSlug' => $clientSlug,
                        'logSlug' => $logSlug
                    )));
        })
        ->bind("home");

    $app->get('/logs/{clientSlug}', function($clientSlug) use($app) {
            $viewer = new \SyonixLogViewer\LogViewer(CONFIG_FILE);

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
            $viewer = new \SyonixLogViewer\LogViewer(CONFIG_FILE);

            $log = $viewer->getLog($clientSlug, $logSlug);

            if($log === false) {
                return $app->redirect($app['url_generator']->generate('client', array('clientSlug' => $clientSlug)));
            }

            return $app['twig']->render('log.html.twig', array(
                    'clients' => $viewer->getClients(),
                    'logs' => $viewer->getLogs($clientSlug),
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
            $viewer = new \SyonixLogViewer\LogViewer(CONFIG_FILE);
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

$app->error(function (\Exception $e, $code) use($app) {
    if ($app['debug']) {
        return;
    }

    switch($code) {
        case 404:
            $viewer = new \SyonixLogViewer\LogViewer(CONFIG_FILE);

            return $app['twig']->render('404.html.twig', array(
            'clients' => $viewer->getClients(),
            'message' => 'The page you are looking for does not exist!',
            'icon' => 'search',
            'error' => false
            ));
        default:
            return $app['twig']->render('error.html.twig', array(
                    'message' => 'Something went wrong!',
                    'icon' => 'bug',
                    'error' => $e
                ));
    }
});

$app->run();
