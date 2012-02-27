<?php
    require_once '../lib/setup.php';

    $nf = new NationalField(NF_APISAMPLE_KEY, NF_APISAMPLE_SECRET, 'iframe');

    if (!$nf->isAuthenticated()) {
        $url = $nf->getAuthenticationUrl();
        // use javascript to framebust
        echo '<script type="text/javascript">top.location.href="'.$url.'";</script>';
        exit();
    }
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>NationalField iframe Sample</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <h1>NationalField iframe Sample</h1>
        <?php $user = $nf->api('users/me'); ?>
        <h2><?php echo htmlentities($user['displayName']) ?></h2>
        <dl>
            <dt>Group</dt>
            <dd><?php echo isset($user['group']) ? htmlentities($user['group']['displayName']) : ' None' ?></dd>
            <dt>Role</dt>
            <dd><?php echo isset($user['role']) ? htmlentities($user['role']['displayName']) : ' None' ?></dd>
            <dt>Manager</dt>
            <dd><?php echo isset($user['manager']) ? htmlentities($user['manager']['displayName']) : ' None' ?></dd>
        </dl>
    </div>
</body>
</html>