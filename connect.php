<?php

require_once(__DIR__ . '/vendor/autoload.php');
(new \Dotenv\Dotenv(__DIR__))->load(); 
use QuickBooksOnline\API\DataService\DataService;

$config = include('config.php');

session_start();

$dataService = DataService::Configure(array(
    'auth_mode' => 'oauth2',
    'ClientID' => env('CLIENT_ID'),
    'ClientSecret' => env('CLIENT_SECRET'),
    'RedirectURI' => env('REDIRECT_URI'),
    'scope' => env('OAUTH_SCOPE'),
    'QBORealmID' => env('COMPANY_ID'),
    'baseUrl' => "Development"
));

$OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
$authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();

$path = 'E:\xampp\htdocs\refreshTest\.env';

echo $authUrl.'<br>'.env('REFRESH_TOKEN').'<br>'.env('ACCESS_TOKEN');


// Store the url in PHP Session Object;
$_SESSION['authUrl'] = $authUrl;

//set the access token using the auth object
if (isset($_SESSION['sessionAccessToken'])) {

    $accessToken = $_SESSION['sessionAccessToken'];
    $accessTokenJson = array('token_type' => 'bearer',
        'access_token' => $accessToken->getAccessToken(),
        'refresh_token' => $accessToken->getRefreshToken(),
        'x_refresh_token_expires_in' => $accessToken->getRefreshTokenExpiresAt(),
        'expires_in' => $accessToken->getAccessTokenExpiresAt()
    );
    $dataService->updateOAuth2Token($accessToken);
    $oauthLoginHelper = $dataService -> getOAuth2LoginHelper();
    $CompanyInfo = $dataService->getCompanyInfo();

    $path = dirname(__DIR__.'/.env').'/.env';

    /**Update Access Token in env file */

    if(is_bool(env('ACCESS_TOKEN')))
    {
        $old = env('ACCESS_TOKEN')? 'true' : 'false';
    }
    elseif(env('ACCESS_TOKEN')===null){
        $old = 'null';
    }
    else{
        $old = env('ACCESS_TOKEN');
    }

    if (file_exists($path)) {
        file_put_contents($path, str_replace(
            "ACCESS_TOKEN=".$old, "ACCESS_TOKEN=".$accessToken->getAccessToken(), file_get_contents($path)
        ));
    }

    /**Update Refresh Token in env file */

    if(is_bool(env('REFRESH_TOKEN')))
    {
        $old = env('REFRESH_TOKEN')? 'true' : 'false';
    }
    elseif(env('REFRESH_TOKEN')===null){
        $old = 'null';
    }
    else{
        $old = env('REFRESH_TOKEN');
    }

    if (file_exists($path)) {
        file_put_contents($path, str_replace(
            "REFRESH_TOKEN=".$old, "REFRESH_TOKEN=".$accessToken->getRefreshToken(), file_get_contents($path)
        ));
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <link rel="apple-touch-icon icon shortcut" type="image/png" href="https://plugin.intuitcdn.net/sbg-web-shell-ui/6.3.0/shell/harmony/images/QBOlogo.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="views/common.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <script>

        var url = '<?php echo $authUrl; ?>';

        var OAuthCode = function(url) {

            this.loginPopup = function (parameter) {
                console.log(parameter);
                this.loginPopupUri(parameter);
            }

            this.loginPopupUri = function (parameter) {

                // Launch Popup
                var parameters = "location=1,width=800,height=650";
                console.log(parameters);
                parameters += ",left=" + (screen.width - 800) / 2 + ",top=" + (screen.height - 650) / 2;
                console.log(parameters);
                console.log(url);
                var win = window.open(url, 'connectPopup', parameters);
                var pollOAuth = window.setInterval(function () {
                    try {

                        if (win.document.URL.indexOf("code") != -1) {
                            window.clearInterval(pollOAuth);
                            win.close();
                            location.reload();
                        }
                    } catch (e) {
                        console.log(e)
                    }
                }, 100);
            }
        }


        var apiCall = function() {
            this.getCompanyInfo = function() {
                /*
                AJAX Request to retrieve getCompanyInfo
                 */
                $.ajax({
                    type: "GET",
                    url: "apiCall.php",
                }).done(function( msg ) {
                    $( '#apiCall' ).html( msg );
                });
            }

            this.refreshToken = function() {
                $.ajax({
                    type: "POST",
                    url: "refreshToken.php",
                }).done(function( msg ) {
                    $( '#apiCall' ).html( msg );
                });
            }
        }

        var oauth = new OAuthCode(url);
        var apiCall = new apiCall();
    </script>
</head>
<body>

<div class="container">

    <h1>
        <a href="http://developer.intuit.com">
            <img src="views/quickbooks_logo_horz.png" id="headerLogo">
        </a>

    </h1>

    <hr>

    <div class="well text-center">

        <h1>QuickBooks HelloWorld sample application</h1>
        <h2>Demonstrate Connect to QuickBooks flow and API Request</h2>

        <br>

    </div>

    <p>If there is no access token or the access token is invalid, click the <b>Connect to QuickBooks</b> button below.</p>
    <pre id="accessToken">
        <style="background-color:#efefef;overflow-x:scroll"><?php
    $displayString = isset($accessTokenJson) ? $accessTokenJson : "No Access Token Generated Yet";
    echo json_encode($displayString, JSON_PRETTY_PRINT); ?>
    </pre>
    <a class="imgLink" href="#" onclick="oauth.loginPopup()"><img src="views/C2QB_green_btn_lg_default.png" width="178" /></a>
    <hr />


    <h2>Make an API call</h2>
    <p>If there is no access token or the access token is invalid, click either the <b>Connect to QucikBooks</b> button above.</p>
    <pre id="apiCall"></pre>
    <button  type="button" class="btn btn-success" onclick="apiCall.getCompanyInfo()">Get Company Info</button>
    <button  type="button" class="btn btn-success" onclick="apiCall.refreshToken()">Refresh Token</button>

    <hr />

</div>
</body>
</html>
