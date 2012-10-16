<?php

// Todo: Add error handling <3

$client_id = 'CLIENT_ID';
$client_secret = 'CLIENT_SECRET';

// This must be the same as defined in your app settings.
$redirect_uri = 'REDIRECT_URI'; 

$step = 'ask';

// If neither code nor error is set, redirect to the authorize page
if(!isset($_GET['code']) && !isset($_GET['error'])) {
    $url = 'https://quote.fm/labs/oauth/authorize';
    $url .= '?response_type=code';
    $url .= '&client_id=' . $client_id;
    $url .= '&redirect_uri='. $redirect_uri;

    $step = 'ask';
}

// Error set. That's sad :(
if(isset($_GET['error'])) {
    $step = 'error';
}

// Code set. Obtain an access token. 
if(isset($_GET['code'])) {
    $params = array(
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
    );

    $json = json_decode(api_request('https://quote.fm/api/oauth/token', 'post', $params));

    // There he is! Now save it somewhere.
    $access_token = $json->access_token;

    // We'll try to get the users timeline! 
    $url = 'https://quote.fm/api/recommendation/listByFollowings?pageSize=5';
    $timeline_obj = json_decode(api_request($url, 'get', array(), $access_token));

    $step = 'done';
}
    
function api_request($url, $method, $params = array(), $access_token = null) {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);

    if($method == 'post') {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
    }

    $headers = array(
            'Expect: ',
    );
    
    if($access_token != null) {
        $headers[] =  'Authorization: Bearer '. $access_token;
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Uncomment if you have any issues
    curl_setopt($curl, CURLOPT_VERBOSE, true);

    $output = curl_exec($curl);
    curl_close($curl);

    return $output;
}


?>


<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>QUOTE.fm API Example</title>

        <style>
            * { margin: 0; padding: 0;}
            body { font-family: sans-serif; }
            .content { width: 335px; margin: 20px auto; padding: 15px; text-align: left;}
            .content * { margin: 15px 0;}
            .content p {line-height: 135%;}
            .content ul {margin-left: 15px;}
        </style>
    </head>
    <body>
        <div class="content">
            <img src="http://placekitten.com/305/200" alt="" />

            <h1>Awesome app for QUOTE.fm</h1>

<?php

switch($step):
    case 'ask':
?>
            <p>Hey, everything will be better with Awesome. Just click this link to allow access to your profile!</p>
            <a href="<?php echo $url ?>">Login with QUOTE.fm</a>
<?php
        break;
    case 'error':
?>
            <p>You clicked deny :/</p>
<?php
        break;
    case 'done':
?>
            <p>Thanks for the access. The last five recommendations from your timeline are from:</p>
            <ul>
                <?php foreach($timeline_obj->entities as $recommendation): ?>
                    <li><?php echo $recommendation->user->fullname ?> (<a href="<?php echo $recommendation->platform_url ?>">Go to recommendation</a>)</li>
                <?php endforeach ?>
            </ul>
<?php
        break;
endswitch;
?>

        </div>      
    </body>
</html>


