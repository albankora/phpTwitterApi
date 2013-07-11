<?php
ini_set('display_errors', 1);
session_start();
date_default_timezone_set('UTC');

if (!empty($_GET)) {
    require_once('request.php');
    if ($_GET['action'] == 'access_token') {
        $respons = requestToken();
        if (!empty($respons['oauth_token'])) {
            header("Location: https://api.twitter.com/oauth/authenticate?oauth_token=" . $respons['oauth_token']);
        } else {
            var_dump($respons);
        }
        exit();
    }
    if ($_GET['action'] == 'token') {
        $respons = oauthVerifier();
        $_SESSION['oauth_token'] = $respons['oauth_token'];
        $_SESSION['oauth_token_secret'] = $respons['oauth_token_secret'];
        $_SESSION['user_id'] = $respons['user_id'];
        $_SESSION['screen_name'] = $respons['screen_name'];
    }
    //view tweets
    if ($_GET['action'] == 'view_twitts') {
        $data = viewTweets();

        p($data, false);
    }
    if ($_GET['action'] == 'followers_list') {
        $data = followersList();
        p($data, false);
    }
    if ($_GET['action'] == 'friends_list') {
        $data = friendsList();
        p($data, false);
    }
    if ($_GET['action'] == 'home_timeline') {
        $data = homeTimeline();
        p($data, false);
    }
    if ($_GET['action'] == 'status_update') {
        $data = statusUpdate();
        p($data, false);
    }
    //account settings
    if ($_GET['action'] == 'account_settings') {
        $data = accountSettings();
        p($data, false);
    }
}

if (isset($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token'])) {
    ?>
    <a href="index.php?action=view_twitts">View Twitts</a>
    <a href="index.php?action=account_settings">Account Settings</a>
    <a href="index.php?action=followers_list">Followers List</a>
    <a href="index.php?action=friends_list">Friends List</a>
    <a href="index.php?action=home_timeline">Home Timeline</a>
    <a href="index.php?action=status_update">Status Update</a>
<?php } else { ?>
    <a href="index.php?action=access_token">Access Token</a>
    <?php
}

function requestToken() {

    $config['oauth_type'] = 'unauthorized';
    $request = new Request($config);
    $data = $request->setRequestInfo('oauth/request_token', 'POST')
            ->buildRequest()
            ->makeRequest();
    $dataArray = explode('&', $data);
    $respons = array();
    foreach ($dataArray as $value) {
        $temp = explode('=', $value);
        $respons[$temp[0]] = $temp[1];
    }
    return $respons;
}

function oauthVerifier() {
    
    $config['oauth_type'] = 'semi-authorized';
    $request = new Request($config);
    $data = $request->setOauthToken($_GET["oauth_token"])
            ->setRequestInfo('oauth/access_token', 'POST', array('oauth_verifier' => $_GET["oauth_verifier"]))
            ->buildRequest()
            ->makeRequest();
    $dataArray = explode('&', $data);
    $respons = array();
    foreach ($dataArray as $value) {
        $temp = explode('=', $value);
        $respons[$temp[0]] = $temp[1];
    }
    return $respons;
}

function viewTweets() {
    $request = new Request();
    $data = $request->setUserCredentials($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'])
            ->setRequestInfo('1.1/statuses/user_timeline.json', 'GET')
            ->buildRequest()
            ->makeRequest();
    return json_decode($data);
}

function accountSettings() {
    $request = new Request();
    $data = $request->setUserCredentials($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'])
            ->setRequestInfo('1.1/account/settings.json', 'GET')
            ->buildRequest()
            ->makeRequest();
    return json_decode($data);
}

function followersList() {

    $request = new Request();
    $data = $request->setUserCredentials($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'])
            ->setRequestInfo('1.1/followers/list.json', 'GET')
            ->buildRequest()
            ->makeRequest();
    return json_decode($data);
}

function friendsList() {
    $request = new Request();
    $data = $request->setUserCredentials($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'])
            ->setRequestInfo('1.1/friends/list.json', 'GET')
            ->buildRequest()
            ->makeRequest();
    return json_decode($data);
}

function homeTimeline() {
    $request = new Request();
    $data = $request->setUserCredentials($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'])
            ->setRequestInfo('1.1/statuses/home_timeline.json', 'GET', array('count' => 1))
            ->buildRequest()
            ->makeRequest();
    return json_decode($data);
}

function statusUpdate() {

    $request = new Request();
    $data = $request->setUserCredentials($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'])
            ->setRequestInfo('1.1/statuses/update.json', 'POST', array('status' => 'This is a post from my new app'))
            ->buildRequest()
            ->makeRequest();
    return json_decode($data);;
    //return $data;
}

function p($var, $die = TRUE) {
    if (is_array($var)) {
        echo '<pre style="background-color:#ededed;border:1px solid #999;padding:20px;">';
        print_r($var);
        echo '</pre>';
    } elseif (is_object($var) || is_bool($var)) {
        echo '<pre style="background-color:#ededed;border:1px solid #999;padding:20px;">';
        var_dump($var);
        echo '</pre>';
    } else {
        echo '<pre style="background-color:#ededed;border:1px solid #999;padding:20px;">';
        echo $var;
        echo '</pre>';
    }

    if ($die === TRUE) {
        die();
    }
}
?>
  