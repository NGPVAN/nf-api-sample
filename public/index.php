<?php
    require_once '../lib/setup.php';

    require_once NF_APISAMPLE_BASEDIR . '/lib/NationalField.php';

    $nf = new NationalField(NF_APISAMPLE_KEY, NF_APISAMPLE_SECRET);

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $nf->setClient($_POST['client']);
                $nf->authenticate();
                break;
            case 'logout':
                $nf->clearAuthentication();
                break;
        }
    } elseif (isset($_GET['code'])) {
        if ($nf->requestToken($_GET['code'])) {
            header('location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>NationalField API Sample</title>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
    <link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/ui-lightness/jquery-ui.css" />
</head>
<body>
	<h1>NationalField API Sample</h1>

	<?php if (!$nf->isAuthenticated()): ?>
        <form method="post">
            <input type="hidden" name="action" value="login" />
            <p>Log in to the Sample API Application via NationalField</p>
            <p>Your Site: https://<input type="text" size="20" name="client" value="testing" />.nationalfield.org</p>
            <p><input type="submit" value="Log In" /></p>
        <form>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="action" value="logout" />
            <p><input type="submit" value="Log Out" /></p>
        <form>
        
        <div id="tabs">
            <ul>
                <li><a href="#tabs-you">You</a></li>
                <li><a href="#tabs-roles">Roles</a></li>
                <li><a href="#tabs-groups">Group</a></li>
                <li><a href="#tabs-stream">Stream</a></li>
                <li><a href="#tabs-updown">Ups & Challenges</a></li>
            </ul>
            <div id="tabs-you">
                <pre><?php print_r($nf->api('users/me')) ?></pre>
            </div>
            <div id="tabs-stream">
                <pre><?php print_r($nf->api('stream')) ?></pre>
            </div>
            <div id="tabs-updown">
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
            <div id="tabs-roles">
                <pre><?php print_r($nf->api('roles')) ?></pre>
            </div>
            <div id="tabs-groups">
                <pre><?php print_r($nf->api('groups')) ?></pre>
            </div>
        </div>
        
        <script>
            $(function() {
                $( "#tabs" ).tabs();
            });
        </script>
    <?php endif; //isAuthenticated ?>
</body>
</html>