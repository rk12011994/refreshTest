<?php

require_once(__DIR__ . '/vendor/autoload.php');
(new \Dotenv\Dotenv(__DIR__))->load(); 
use QuickBooksOnline\API\DataService\DataService;

session_start();

function refreshToken()
{

    // Create SDK instance
    $config = include('config.php');
     /*
     * Retrieve the accessToken value from session variable
     */
    $accessToken = $_SESSION['sessionAccessToken'];
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => env('CLIENT_ID'),
        'ClientSecret' => env('CLIENT_SECRET'),
        'RedirectURI' => env('REDIRECT_URI'),
        'refreshTokenKey' => env('REFRESH_TOKEN'),
        'baseUrl' => "Development",
        'QBORealmID' => env('COMPANY_ID')
    ));

    /*
     * Update the OAuth2Token of the dataService object
     */
    $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
    $refreshedAccessTokenObj = $OAuth2LoginHelper->refreshToken();
    $dataService->updateOAuth2Token($refreshedAccessTokenObj);

    $_SESSION['sessionAccessToken'] = $refreshedAccessTokenObj;

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
            "ACCESS_TOKEN=".$old, "ACCESS_TOKEN=".$refreshedAccessTokenObj->getAccessToken(), file_get_contents($path)
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
            "REFRESH_TOKEN=".$old, "REFRESH_TOKEN=".$refreshedAccessTokenObj->getRefreshToken(), file_get_contents($path)
        ));
    }

    print_r($refreshedAccessTokenObj);
    return $refreshedAccessTokenObj;
}

$result = refreshToken();

?>