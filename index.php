<?php 
    ini_set("display_errors", 1);
    ini_set("track_errors", 1);
    ini_set("html_errors", 1);
    error_reporting(E_ERROR);
    date_default_timezone_set('Europe/Zurich');
    setlocale(LC_ALL, 'de_DE.utf8');
    
    require_once('bootstrap.php');
    $app = new \SyonixLogViewer\LogViewer('./config/config.json');
    $baseUrl = ($_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . str_replace('/index.php', '', $_SERVER['PHP_SELF']);
    
    session_start();
    
    /* Logout */
    if(isset($_GET['a']) && $_GET['a'] == 'logout') {
        $_SESSION['authenticated'] = false;
		session_destroy();
		header('Location: '.$baseUrl);
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
            header('Location: '.$baseUrl.'/'.$_SESSION['client'].'/'.$_SESSION['log']);
        }
    }
    
    /* Define Icons and CSS classes */
    $logLevelAppearance = array(
        100 => array('icon'=>'bug', 'class'=>'debug'),
        200 => array('icon'=>'info-circle', 'class'=>'info'),
        250 => array('icon'=>'file-text', 'class'=>'notice'),
        300 => array('icon'=>'warning', 'class'=>'warning'),
        400 => array('icon'=>'times-circle', 'class'=>'error'),
        500 => array('icon'=>'fire', 'class'=>'critical'),
        550 => array('icon'=>'bell', 'class'=>'alert'),
        600 => array('icon'=>'flash', 'class'=>'emergency'),
    );

?>
<html>
    <head>
        <title>Syonix Monolog Viewer</title>

        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta charset="utf-8">
        <!-- Bootstrap -->
        <link href="<?php echo $baseUrl; ?>/res/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
        <link href="http://syonix.ch/res/fonts/flexo.css" rel="stylesheet" media="screen">
        <link href="<?php echo $baseUrl; ?>/res/font-awesome/css/font-awesome.min.css" rel="stylesheet" media="screen">
        <link href="<?php echo $baseUrl; ?>/css/screen.css" rel="stylesheet" media="screen">
        
        <!-- Apple Touch Icons -->
        <link rel="apple-touch-icon" href="<?php echo $baseUrl; ?>/img/touch-icon-iphone.png">
        <link rel="apple-touch-icon" sizes="76x76" href="<?php echo $baseUrl; ?>/img/touch-icon-ipad.png">
        <link rel="apple-touch-icon" sizes="120x120" href="<?php echo $baseUrl; ?>/img/touch-icon-iphone-retina.png">
        <link rel="apple-touch-icon" sizes="152x152" href="<?php echo $baseUrl; ?>/img/touch-icon-ipad-retina.png">
        
        <!-- Web App Tags -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black" />
        <meta name="mobile-web-app-capable" content="yes">
        <link rel="apple-touch-startup-image" href="<?php echo $baseUrl; ?>/img/touch-startup-ipad-portrait.png"    sizes="768x1004" media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:portrait)" />
        <link rel="apple-touch-startup-image" href="<?php echo $baseUrl; ?>/img/touch-startup-ipad-landscape.png"     sizes="1024x748" media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:landscape)" />


        
        <!-- Java Scripts -->
        <script src="<?php echo $baseUrl; ?>/res/jquery/jquery-2.0.3.min.js"></script>
        <script src="<?php echo $baseUrl; ?>/res/jquery/jquery.stayInWebApp.min.js"></script>
        <script src="<?php echo $baseUrl; ?>/res/retina.js"></script>
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
            
            $(document).ready(function(){
                $("#filter-form-js").show();
                
                $("#filter-form-toggle").click(function() {
                    if($("#filter-form-dropdown").is(":visible")) {
                        $("#filter-form-arrow").removeClass("fa-chevron-up");
                        $("#filter-form-arrow").addClass("fa-chevron-down");
                        $(this).removeClass("active");
                        $("#filter-form-dropdown").slideUp(200);
                    } else {
                        $("#filter-form-arrow").removeClass("fa-chevron-down");
                        $("#filter-form-arrow").addClass("fa-chevron-up");
                        $(this).addClass("active");
                        $("#filter-form-dropdown").slideDown(200);
                    }                    
                });
            });
        </script>
            
        <!-- Favicon -->
        <link rel="shortcut icon" href="<?php echo $baseUrl; ?>/img/favicon.ico"/>
    </head>
    <body>
        <header id="header">
            <a href="<?php echo $baseUrl; ?>"><img class="logo" src="<?php echo $baseUrl; ?>/img/logo.png" alt="Syonix"></a>
            <?php if($_SESSION['authenticated'] === true): ?><div id="logout"><a href="<?php echo $baseUrl; ?>?a=logout">Logout <i class="fa fa-sign-out"></i></a></div><?php endif;?>
        </header>
        <?php if($_SESSION['authenticated'] === true): ?>
        <nav id="navigation">
            <ul>
                <?php foreach($app->getClients() as $slug => $name): ?>
                <li<?php if($slug==$_SESSION['client']) echo ' class="active turquoise"'?>>
                  <a href="<?php echo $baseUrl . '/' . $slug; ?>"><?php echo $name; ?></a>
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
                      echo '><a href="'.$baseUrl.'/' . $_SESSION['client'] . '/' . $slug . '">' . $log['name'] . '</a></li>';
                  }
                  
                  $minLogLevel = (in_array($_GET['f'], Monolog\Logger::getLevels()) ? $_GET['f'] : 0);
                ?>
                <li id="filter-form-js" class="pull-right <?php if($minLogLevel > 0) echo ' active'; ?>" style="display:none;">
                    <a id="filter-form-toggle" href="javascript:void(0);"><i class="fa fa-lg fa-filter"></i> Minimal Log Level <i id="filter-form-arrow" class="fa fa-chevron-down"></i></a>
                </li>
                <noscript>
                    <li id="filter-form" class="pull-right">
                        <form method="get">
                            <label><i class="fa fa-filter"></i></label>
                            <select name="f">
                                <option value="">Minimal Log Level</option>
                                <?php foreach(Monolog\Logger::getLevels() as $name => $value) {
                                    echo '<option value="'.$value.'"';
                                    if($_GET['f'] == $value) echo ' selected="selected"';
                                    echo '>'.$name.'</option>';
                                } ?>
                            </select>
                            <input type="submit" value="Filter">
                        </form>
                    </li>
                </noscript>
                <li class="pull-right"><a href="#" onclick="$('#content').animate({ scrollTop: $('#content').prop('scrollHeight') - $('#content').height() }, 500); return false;"><i class="fa fa-lg fa-arrow-circle-down"></i> Newest entry</a></li>
            </ul>
            <div id="filter-form-dropdown" style="display:none;">
                <?php
                    foreach(Monolog\Logger::getLevels() as $name => $value) {
                        $base = $baseUrl . '/' . $_GET['c'] . '/' . $_GET['l'];
                        $href = ($value > 100 ? $base . '/' . $value : $base);
                        echo '<a href="'.$href.'"';
                        if($value < $minLogLevel) echo ' class="lower"';
                        echo '><i class="fa fa-fw fa-'.$logLevelAppearance[$value]['icon'].'"></i> '. $name . '</a><br />';
                    }
                ?>
            </div>
        </header>
        <div id="content">
          <?php
              if($app->hasLogs()) {
                  $log = $app->getLog($_SESSION['client'], $_SESSION['log']);
                  if($log instanceof SyonixLogViewer\LogFile) {
                      foreach($log->getLines() as $id => $line) {
                          $level = constant('Monolog\Logger::'.$line['level']);
                          if($level >= $minLogLevel)
                          {
                              $message = ($line['message'] != "" ? $line['message'] : '<span style="color: #cbcbcb; font-style: italic;">No Message</span>');
                              echo '<div class="logline clearfix level-'.$logLevelAppearance[$level]['class'].'">';
                              echo '<div class="level level-'.$logLevelAppearance[$level]['class'].'"><i class="fa fa-'.$logLevelAppearance[$level]['icon'].'"></i>&nbsp;</div>';
                              echo '<div class="message';
                              if(count($line['context']) > 0) echo ' pointer" onclick="toggleMore('.($id+1).');';
                              echo '">'.$message.'</div>';
                              echo '<div class="date">'.$line['date']->format("d.m.Y, H:i:s").'</div>';
                              if(count($line['context']) > 0) echo '<div class="more" id="more-'.($id+1).'" onclick="toggleMore('.($id+1).');"><i class="fa fa-search-plus"></i> more...</div>';
                              echo '<div class="context" id="context-'.($id+1).'"><table>';
                              
                              foreach($line['context'] as $title => $content) {
                                  echo '<tr><td><strong>' . $title . '</strong></td>';
                                  echo '<td>' . nl2br($content) . '</td></tr>';
                              }
                              echo '</table></div>';
                              echo '</div>';
                          }
                      } 
                  } else {
                      echo '<div class="alert alert-danger alert-dismissable"><b>An error has occured!</b></div>';
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