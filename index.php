<?php 
    ini_set("display_errors", 1);
    ini_set("track_errors", 1);
    ini_set("html_errors", 1);
    error_reporting(E_ERROR);
    date_default_timezone_set('Europe/Berlin');
    setlocale(LC_ALL, 'de_DE.utf8');
    
    require_once('bootstrap.php');
    $app = new \SyonixLogViewer\LogViewer('./config/config.json');
    
    session_start();
    
    /* Logout */
    if(isset($_GET['a']) && $_GET['a'] == 'logout') {
        $_SESSION['authenticated'] = false;
		session_destroy();
		header("Location: http://" . $_SERVER['SERVER_NAME']);
	}
    
    /* Setup login */
    if(isset($_POST['setup'])) {
        if($_POST['password'] == $_POST['password-repeat']) {
            if(is_dir('./secure/pwd')) rmdir('./secure/pwd');
            if(mkdir('./secure/pwd', 0700)) {
                file_put_contents('./secure/pwd/'.uniqid(), password_hash($_POST['password'], PASSWORD_DEFAULT));
                $setupSuccessful = true;
            } else {
                $setupFailed = true;
            }
        } else {
            $setupNomatch = true; 
        }
    }
    
    /* Check Passwd File */
    $checkPasswdFile = false;
    $files = glob("secure/pwd/*");
    if(count($files) == 1) {
        $checkPasswdFile = true;
        $passwordFile = $files[0];
    } else if(count($files) > 1) {
        rmdir('./secure/pwd');
    }
    
    /* Login */
    
    if(isset($_POST['login']) && $checkPasswdFile === true) {
        $password = file_get_contents($passwordFile);
        if(password_verify($_POST['password'], $password)) {
            session_regenerate_id(true); 
            $_SESSION['authenticated'] = true;
        } else {
            $_SESSION['authenticated'] = false;
        }
    }
	    
    /* Load Logfiles */
    
    if($_SESSION['authenticated'] === true && $app->hasLogs()) {
        $redirect = false;
        if(isset($_GET['c']) && $app->clientExists($_GET['c'])) $_SESSION['client'] = $_GET['c'];
        if(isset($_GET['l']) && $app->logExists($_GET['c'], $_GET['l'])) $_SESSION['log'] = $_GET['l'];
        if(!isset($_GET['c'])) {
            $_SESSION['client'] = $app->getFirstClient();
            $redirect = true;
        }
        if(!isset($_GET['l'])) {
            $_SESSION['log'] = $app->getFirstLog($_SESSION['client']);
            $redirect = true;
        }
        if($redirect === true) {
            header("Location: /".$_SESSION['client'] . "/" . $_SESSION['log']);
        }
    }
?>
<html>
    <head>
        <title>Syonix Monolog Viewer</title>

        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta charset="utf-8">
        <!-- Bootstrap -->
        <link href="/res/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
        <link href="http://syonix.ch/res/fonts/flexo.css" rel="stylesheet" media="screen">
        <link href="/res/font-awesome/css/font-awesome.min.css" rel="stylesheet" media="screen">
        <link href="/css/screen.css" rel="stylesheet" media="screen">
        
        <!-- Apple Touch Icons -->
        <link rel="apple-touch-icon" href="/img/touch-icon-iphone.png">
        <link rel="apple-touch-icon" sizes="76x76" href="/img/touch-icon-ipad.png">
        <link rel="apple-touch-icon" sizes="120x120" href="/img/touch-icon-iphone-retina.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/img/touch-icon-ipad-retina.png">
        
        <!-- Web App Tags -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black" />
        <meta name="mobile-web-app-capable" content="yes">
        <link rel="apple-touch-startup-image" href="/img/touch-startup-ipad-portrait.png"    sizes="768x1004" media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:portrait)" />
        <link rel="apple-touch-startup-image" href="/img/touch-startup-ipad-landscape.png"     sizes="1024x748" media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:landscape)" />


        
        <!-- Java Scripts -->
        <script src="/res/jquery/jquery-2.0.3.min.js"></script>
        <script src="/res/jquery/jquery.stayInWebApp.min.js"></script>
        <script src="/res/retina.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                $("div.context").hide();
                $.stayInWebApp();
            });
            
            function toggleMore(id) {
                context = $('#context-'+id);
                more = $('#more-'+id);
                if(context.is(':visible')) {
                    context.slideUp(300);
                    more.html('<i class="fa fa-search-plus"></i> more...');
                } else {
                    context.slideDown(300);
                    more.html('<i class="fa fa-search-minus"></i> less...');
                }
            }
        </script>
            
        <!-- Favicon -->
        <link rel="shortcut icon" href="/img/favicon.ico"/>
    </head>
    <body>
        <header id="header">
          <a href="/"><img class="logo" src="/img/logo.png" alt="Syonix"></a>
          <?php if($_SESSION['authenticated'] === true): ?><div id="logout"><a href="/?a=logout">Logout <i class="fa fa-sign-out"></i></a></div><?php endif;?>
        </header>
        <?php if($_SESSION['authenticated'] === true): ?>
        <nav id="navigation">
          <ul>
              <?php foreach($app->getClients() as $slug => $name): ?>
              <li<?php if($slug==$_SESSION['client']) echo ' class="active turquoise"'?>>
                  <a href="/<?php echo $slug; ?>"><?php echo $name; ?></a>
              </li>
              <?php endforeach; ?>
          </ul>
        </nav>
        <header id="logs">
          <ul>
              <?php 
                  foreach($app->getLogs($_SESSION['client']) as $slug => $log) {
                      echo '<li';
                      if($_SESSION['log'] == $slug) echo ' class="active"';
                      echo '><a href="/' . $_SESSION['client'] . '/' . $slug . '">' . $log['name'] . '</a></li>';
                  }
              ?>
              <li class="pull-right"><a href="#" onclick="$('#content').animate({ scrollTop: $('#content').prop('scrollHeight') - $('#content').height() }, 500); return false;"><i class="fa fa-arrow-circle-down"></i> Jump to newest entry</a></li>
          </ul>
        </header>
        <div id="content">
          <?php
              if($app->hasLogs()) {
                  $log = $app->getLog($_SESSION['client'], $_SESSION['log']);
                  if($log instanceof SyonixLogViewer\LogFile) {
                      foreach($log->getLines() as $id => $line) {
                          $message = ($line['message'] != "") ? $line['message'] : '<span style="color: #cbcbcb; font-style: italic;">No Message</span>';
                          switch($line['level']) {
                              case 'DEBUG':
                                $levelIcon = 'bug';
                                $cssClass = 'debug';
                                break;
                              case 'INFO':
                                $levelIcon = 'info-circle';
                                $cssClass = 'info';
                                break;
                              case 'NOTICE':
                                $levelIcon = 'file-text';
                                $cssClass = 'notice';
                                break;
                              case 'WARNING':
                                $levelIcon = 'warning';
                                $cssClass = 'warning';
                                break;
                              case 'ERROR':
                                $levelIcon = 'times-circle';
                                $cssClass = 'error';
                                break;
                              case 'CRITICAL':
                                $levelIcon = 'fire';
                                $cssClass = 'critical';
                                break;
                              case 'ALERT':
                                $levelIcon = 'bell';
                                $cssClass = 'alert';
                                break;
                              case 'EMERGENCY':
                                $levelIcon = 'flash';
                                $cssClass = 'emergency';
                                break;
                          }
                          echo '<div class="logline clearfix">';
                          echo '<div class="level '.$cssClass.'"><i class="fa fa-'.$levelIcon.'"></i>&nbsp;</div>';
                          echo '<div class="message">'.$message.'</div>';
                          echo '<div class="date">'.$line['date']->format("d.m.Y, H:i:s").'</div>';
                          echo '<div class="more" id="more-'.($id+1).'" onclick="toggleMore('.($id+1).');"><i class="fa fa-search-plus"></i> more...</div>';
                          echo '<div class="context" id="context-'.($id+1).'"><table>';
                          
                          foreach($line['context'] as $title => $content) {
                              echo '<tr><td><strong>' . $title . '</strong></td>';
                              echo '<td>' . nl2br($content) . '</td></tr>';
                          }
                          echo '</table></div>';
                          echo '</div>';
                      } 
                  } else {
                      echo "An error has occured!";
                  }
              } else {
                  echo '<div class="alert alert-danger alert-dismissable"><b>Error</b> - No accessible logs were found. Please check your config file.</div>';
              }
              
          ?>
        </div>
        <?php elseif($checkPasswdFile === true): ?>
        <div id="login">
            <div id="loginform">
                <form method="post">
                    <?php
                        if($setupSuccessful === true) {
                            echo '<div class="alert alert-success alert-dismissable"><b>Congratulations</b> - Your login has successfully been created. You can now sign in using the password you created.</div>';
                        }
                    ?>
                    <p>Please enter your password to access the log files.</p>
                    <?php
                        if(isset($_POST['login']) && $_SESSION['authenticated'] !== true) {
                            echo '<div class="alert alert-danger alert-dismissable"><b>Error</b> - Password not correct.</div>';
                        }
                    ?>
                    <input type="password" class="form-control" name="password" placeholder="Password">
                    <input type="submit" class="btn btn-primary btn-block" name="login" value="Sign in">
                </form>
            </div>
        </div>
        <?php else: ?>
        <div id="login">
            <div id="loginform">
                <form method="post">
                    <div class="alert alert-info">
                        <p>Thank you for installing Syonix Monolog Viewer. This little tool helps you to beautifully display log files generated by <a href="https://github.com/Seldaek/monolog" target="_blank" class="alert-link">Monolog</a>.</p>
                        <p><strong>Please enter a password to be used to secure this applcation.</strong></p>
                    </div>
                    <?php
                        if($setupNomatch === true) {
                            echo '<div class="alert alert-danger alert-dismissable"><b>Error</b> - The provided Passwords do not match.</div>';
                        } if($setupFailed === true) {
                            echo '<div class="alert alert-danger alert-dismissable"><b>Error</b> - Could not save the password. Please make sure that the directory <code>/secure</code> is writable.</div>';
                        }
                    ?>
                    <input type="password" class="form-control" name="password" placeholder="Password">
                    <input type="password" class="form-control" name="password-repeat" placeholder="Repeat password">
                    <input type="submit" class="btn btn-primary btn-block" name="setup" value="Create login">
                </form>
            </div>
        </div>
        <?php endif; ?>
    </body>
</html>