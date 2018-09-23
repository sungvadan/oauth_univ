<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Client;

class CoopOAuthController extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/oauth/start', array(new self(), 'redirectToAuthorization'))->bind('coop_authorize_start');
        $routing->get('/coop/oauth/handle', array(new self(), 'receiveAuthorizationCode'))->bind('coop_authorize_redirect');
    }

    /**
     * This page actually redirects to the COOP authorize page and begins
     * the typical, "auth code" OAuth grant type flow.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function redirectToAuthorization(Request $request)
    {
        $redirectUri = $this->generateUrl('coop_authorize_redirect',
            array(),
            true
        );
        $url = 'http://coop.apps.symfonycasts.com/authorize?'.http_build_query(array(
            'response_type' => 'code',
            'client_id' => 	'top count',
            'redirect_uri' => $redirectUri,
            'scope' => 'profile eggs-count',
        ));

        return $this->redirect($url);
    }

    /**
     * This is the URL that COOP will redirect back to after the user approves/denies access
     *
     * Here, we will get the authorization code from the request, exchange
     * it for an access token, and maybe do some other setup things.
     *
     * @param  Application             $app
     * @param  Request                 $request
     * @return string|RedirectResponse
     */
    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // equivalent to $_GET['code']
        $code = $request->get('code');

        if(!$code){
            $error = $request->get('error');
            $errorDescription = $request->get('error_description');

            return $this->render('failed_authorization.twig', array(
               'response' => array(
                   'error' => $error,
                   'errorDescription' => $errorDescription
               )
            ));
        }
        $redirectUri = $this->generateUrl('coop_authorize_redirect',
            array(),
            true
        );

        $http = new Client('http://coop.apps.symfonycasts.com', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $request = $http->post('/token', null,[
            'client_id' => 	'top count',
            'client_secret' => 'ec5523afca49c499e5aaf7a856cc936c',
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri
        ]);
        $response = $request->send();
        $responseBody = $response->getBody(true);
        $responseArr = json_decode($responseBody, true);
        if(!isset($responseArr['access_token'])){
            return $this->render('failed_token_request.twig',array(
               'response' => $responseArr ? $responseArr : $response
            ));
        }

        $accessToken = $responseArr['access_token'];
        $expiresIn = $responseArr['expires_in'];
        $expiresAt = new \DateTime('+'.$expiresIn.' seconds');
        $refreshToken = $responseArr['refresh_token'];

        $request = $http->get('/api/me');
        $request->addHeader('Authorization', 'Bearer '.$accessToken);
        $response = $request->send();

        $json = json_decode($response->getBody(), true);
        if($this->isUserLoggedIn()){
            $user = $this->getLoggedInUser();
        }else{
            $user = $this->findOrCreateUser($json);
            $this->loginUser($user);
        }
        $user->coopUserId = $json['id'];
        $user->coopAccessToken = $accessToken;
        $user->coopAccessExpiresAt = $expiresAt;
        $user->coopRefreshToken = $refreshToken;

        $this->saveUser($user);

        return $this->redirect($this->generateUrl('home'));
    }

    private function findOrCreateUser(array $metaData)
    {
        if($user = $this->findUserByCOOPId($metaData['id'])){
            return $user;
        }
        if($user = $this->findUserByEmail($metaData['email'])){
            return $user;
        }
        return $this->createUser(
            $metaData['email'],
            '',
            $metaData['firstName'],
            $metaData['lastName']
        );
    }
}
