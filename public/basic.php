<?php
    require_once '../lib/setup.php';

    require_once NF_APISAMPLE_BASEDIR . '/lib/NationalField.php';

    $nf = new NationalField(NF_APISAMPLE_KEY, NF_APISAMPLE_SECRET, 'basic');

    if (isset($_POST['action'])) {
        // login/logout
        switch ($_POST['action']) {
            case 'login':
                $nf->setClient($_POST['client']);
                $nf->redirectForAuthentication();
                break;
            case 'logout':
                $nf->clearAuthentication();
                break;
        }
    } elseif (isset($_GET['code'])) {
        // authorization response
        if ($nf->completeAuthentication($_GET['code'])) {
            header('location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>NationalField API Sample</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
        }
    </style>

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>

<body>

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="brand" href="#">NationalField API Sample</a>
        </div>
      </div>
    </div>

    <div class="container">
    	<?php if (!$nf->isAuthenticated()): ?>
            <form method="post">
                <input type="hidden" name="action" value="login" />
                <p>Log in to the Sample API Application via NationalField</p>
                <p>Your Site: https://<input type="text" size="20" name="client" value="testing" />.nationalfield.org</p>
                <p><input type="submit" value="Log In" /></p>
            </form>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="logout" />
                <p><input type="submit" value="Log Out" /></p>
            </form>
            
            <div class="tabbable">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#tabs-you" data-toggle="tab">You</a></li>
                    <li><a href="#tabs-roles" data-toggle="tab">Roles</a></li>
                    <li><a href="#tabs-groups" data-toggle="tab">Group</a></li>
                    <li><a href="#tabs-stream" data-toggle="tab">Stream</a></li>
                    <li><a href="#tabs-updown" data-toggle="tab">Ups & Challenges</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="tabs-you">
                        <pre><?php print_r($nf->api('users/me')) ?></pre>
                    </div>
                    <div class="tab-pane" id="tabs-stream">
                        <pre><?php print_r($nf->api('stream')) ?></pre>
                    </div>
                    <div class="tab-pane" id="tabs-updown">
                        <?php
                            $upsandchallenges = $nf->api('upsandchallenges');
                            $table = array();
                            foreach ($upsandchallenges as $ud) {
                                $userId = $ud['actor']['id'];
                                if (!isset($table[$userId])) {
                                    $table[$userId] = array(
                                        'id' => $userId,
                                        'name' => $ud['actor']['displayName'],
                                        'counts' => array(
                                            'ups' => 0,
                                            'challanges' => 0,
                                            'changes' => 0
                                        )
                                    );
                                }
                                if ($ud['object']['content']['up']) $table[$userId]['counts']['ups']++;
                                if ($ud['object']['content']['down']) $table[$userId]['counts']['challanges']++;
                                if ($ud['object']['content']['change']) $table[$userId]['counts']['changes']++;
                            }
                        ?>
                            <table>
                            <tr>
                                <th>User</th>
                                <th>Ups</th>
                                <th>Challenges</th>
                                <th>Changes</th>
                            </tr>
                            <?php
                                foreach($table as $row) {
                                    echo '<tr><td>' . htmlentities($row['name']) . '</td>' .
                                         '<td>' . number_format($row['counts']['ups']) . '</td>' .
                                         '<td>' . number_format($row['counts']['challanges']) . '</td>' .
                                         '<td>' . number_format($row['counts']['changes']) . '</td></tr>';
                                }
                            ?>
                            </table>
                    </div>
                    <div class="tab-pane" id="tabs-roles">
                        <pre><?php print_r($nf->api('roles')) ?></pre>
                    </div>
                    <div class="tab-pane" id="tabs-groups">
                        <pre><?php print_r($nf->api('groups')) ?></pre>
                    </div>
                    
                </div>
            </div>
        <?php endif; //isAuthenticated ?>
    </div> <!-- /container -->

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

</body>
</html>