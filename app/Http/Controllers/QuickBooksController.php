<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Customer;
use App\Customers;

class QuickBooksController extends Controller
{
    public function connect() {

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

        echo $authUrl.'<br>'.session('accessToken');

        $accessTokenJson = array();

        if(session('accessToken') != null) {
            $accessToken = session('accessToken');
            $accessTokenJson = array('token_type' => 'bearer',
                'access_token' => $accessToken->getAccessToken(),
                'refresh_token' => $accessToken->getRefreshToken(),
                'x_refresh_token_expires_in' => $accessToken->getRefreshTokenExpiresAt(),
                'expires_in' => $accessToken->getAccessTokenExpiresAt()
            );
            $dataService->updateOAuth2Token($accessToken);
            $oauthLoginHelper = $dataService -> getOAuth2LoginHelper();
            $CompanyInfo = $dataService->getCompanyInfo();
        }

        return view('auth')->with(compact(['authUrl', 'accessTokenJson']));
    }

    public function callback() {

        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => env('CLIENT_ID'),
            'ClientSecret' => env('CLIENT_SECRET'),
            'RedirectURI' => env('REDIRECT_URI'),
            'scope' => env('OAUTH_SCOPE'),
            'baseUrl' => "Development"
        ));

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        parse_str($_SERVER['QUERY_STRING'], $qsArrray);
        $parseUrl = array(
            'code' => $qsArray['code'],
            'realmId' => $qsArray['realmId']
        );
        /*
        * Update the OAuth2Token
        */
        $accessToken = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($parseUrl['code'], $parseUrl['realmId']);
        $dataService->updateOAuth2Token($accessToken);

        session(['accessToken' => $accessToken]);
        session(['realmId' => $parseUrl['realmId']]);

        /**Update Access Token in env file */
        $path = base_path('.env');

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
        $path = base_path('.env');

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
        $path = base_path('.env');

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
    }

    public function createCustomer() {
        
        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => env('CLIENT_ID'),
            'ClientSecret' => env('CLIENT_SECRET'),
            'accessTokenKey' => env('ACCESS_TOKEN'),
            'refreshTokenKey' => env('REFRESH_TOKEN'),
            'QBORealmID' => env('COMPANY_ID'),
            'baseUrl' => "Development"
        ));

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

        if(session('customer_id') != null) {
            $customer = Customers::find(session('customer_id'));
            $theResourceObj = Customer::create([
                "GivenName" => $customer->first_name,
                "FamilyName" => $customer->last_name,
                "FullyQualifiedName" => $customer->full_name,
                "PrimaryPhone" => [
                    "FreeFormNumber"  => $customer->phone
                ],
                "DisplayName" => $customer->full_name.' '.$customer->phone
            ]);

            $resultingObj = $dataService->Add($theResourceObj);

            $error = $dataService->getLastError();
            if ($error) {
                echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
                echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
                echo "The Response message is: " . $error->getResponseBody() . "\n";
            }
            else {
                $input['QuickBooksID'] = $resultingObj->Id;
                $update = $customer->update($input);
                echo $data = "Created Id={$resultingObj->Id}. Reconstructed response body:\n\n";
                $xmlBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($resultingObj, $urlResource);
                $data = $data.' '.$xmlBody;
                file_put_contents('data.txt', $data, FILE_APPEND);
                
                /**Code to append in env file starts here */
                /* $path = base_path('.env');

                if(is_bool(env('REDIRECT_URI')))
                {
                    $old = env('REDIRECT_URI')? 'true' : 'false';
                }
                elseif(env('REDIRECT_URI')===null){
                    $old = 'null';
                }
                else{
                    $old = env('REDIRECT_URI');
                }

                if (file_exists($path)) {
                    file_put_contents($path, str_replace(
                        "REDIRECT_URI=".$old, "REDIRECT_URI=IMTHENEWVALUE", file_get_contents($path)
                    ));
                } */
                /**Code to append in env file ends here */

                return redirect()->route('home')->with(['status' => 'Added To QuickBooks Already']);
            }
        }
    }
}
