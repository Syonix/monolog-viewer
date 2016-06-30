<?php

$ctrl = $app['controllers_factory'];

$ctrl->match('/', function (\Symfony\Component\HttpFoundation\Request $request) use ($app) {
    $error = "";
    if ($request->getMethod() == "POST") {
        if ($request->get('password') == $request->get('password-repeat')) {
            if (is_writable(PASSWD_DIR)) {
                $user = new \Symfony\Component\Security\Core\User\User('user', []);
                $encoder = $app['security.encoder_factory']->getEncoder($user);
                $password = $encoder->encodePassword($request->get('password'), '');

                file_put_contents(PASSWD_FILE, $password);

                return $app['twig']->render('login.html.twig', [
                    'create_success' => true,
                    'error'          => false
                ]);
            } else {
                $error = 'Could not save the password. Please make sure your server can write the directory (<code>/app/config/secure/</code>).';
            }
        } else {
            $error = 'The provided Passwords do not match.';
        }
    }
    return $app['twig']->render('set_pwd.html.twig', [
        'error' => $error
    ]);
})
    ->bind("home")
    ->method('POST|GET');

$ctrl->match('/{url}', function (\Symfony\Component\HttpFoundation\Request $request) use ($app) {
    return $app->redirect($app['url_generator']->generate('home'));
})
    ->assert('url', '.+'); // Match any route;

return $ctrl;
