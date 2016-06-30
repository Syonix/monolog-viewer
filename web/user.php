<?php

$ctrl = $app['controllers_factory'];

$ctrl->get('/', function () use ($app) {
    if (!is_readable(CONFIG_FILE)) {
        throw new \Syonix\LogViewer\Exceptions\ConfigFileMissingException();
    }
    return $app->redirect($app['url_generator']->generate('logs'));
})
    ->bind("home");

$ctrl->get('/login', function (\Symfony\Component\HttpFoundation\Request $request) use ($app) {
    return $app['twig']->render('login.html.twig', [
        'create_success' => false,
        'error'          => $app['security.last_error']($request),
    ]);
})
    ->bind("login");

$ctrl->get('/logs{path}', function () use ($app) {
    return $app['twig']->render('log.html.twig', [
        'reverse_order' => $app['config']['reverse_line_order']
    ]);
})
    ->bind("logs")
    ->value('path', FALSE)
    ->assert("path", ".*");

return $ctrl;