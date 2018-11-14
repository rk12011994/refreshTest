<?php

require_once(__DIR__ . '/vendor/autoload.php');
(new \Dotenv\Dotenv(__DIR__))->load(); 
use QuickBooksOnline\API\DataService\DataService;

session_start();

function processCode()
{

    // Create SDK instance
    $config = include('config.php');
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => env('CLIENT_ID'),
        'ClientSecret' => env('CLIENT_SECRET'),
        'RedirectURI' => env('REDIRECT_URI'),
        'scope' => env('OAUTH_SCOPE'),
        'baseUrl' => "Development"
    ));

    $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
    $parseUrl = parseAuthRedirectUrl($_SERVER['QUERY_STRING']);

    /*
     * Update the OAuth2Token
     */
    $accessToken = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($parseUrl['code'], $parseUrl['realmId']);
    $dataService->updateOAuth2Token($accessToken);

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

        /**Update Company Id */
        if(is_bool(env('COMPANY_ID')))
        {
            $old = env('COMPANY_ID')? 'true' : 'false';
        }
        elseif(env('COMPANY_ID')===null){
            $old = 'null';
        }
        else{
            $old = env('COMPANY_ID');
        }

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                "COMPANY_ID=".$old, "COMPANY_ID=".$parseUrl['realmId'], file_get_contents($path)
            ));
        }

    /*
     * Setting the accessToken for session variable
     */
    $_SESSION['sessionAccessToken'] = $accessToken;
    $_SESSION['realmId'] = $parseUrl['realmId'];
}

function parseAuthRedirectUrl($url)
{
    parse_str($url,$qsArray);
    return array(
        'code' => $qsArray['code'],
        'realmId' => $qsArray['realmId']
    );
}

$result = processCode();

?>