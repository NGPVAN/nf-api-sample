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
        <h2>Groups:</h2>
        <pre><?php print_r($nf->api('groups')) ?></pre>
        <h2>Roles:</h2>
        <pre><?php print_r($nf->api('roles')) ?></pre>
    <?php endif; //isAuthenticated ?>
</body>
</html>