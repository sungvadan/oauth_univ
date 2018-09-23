<?php

namespace OAuth2Demo\Client\Controllers;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class FacebookOAuthController extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/facebook/oauth/start', array(new self(), 'redirectToAuthorization'))->bind('facebook_authorize_start');
        $routing->get('/facebook/oauth/handle', array(new self(), 'receiveAuthorizationCode'))->bind('facebook_authorize_redirect');

        $routing->get('/coop/facebook/share', array(new self(), 'shareProgressOnFacebook'))->bind('facebook_share_place');
    }

    /**
     * This page actually redirects to the Facebook authorize page and begins
     * the typical, "auth code" OAuth grant type flow.
     *
     * @return RedirectResponse
     */
    public function redirectToAuthorization()
    {
        $facebook = $this->createFacebook();

        $redirectUrl = $this->generateUrl('facebook_authorize_redirect',array(),true);
        $helper  = $facebook->getRedirectLoginHelper();
        $permissions = ['publish_pages','manage_pages' ,'publish_to_groups'];
        $loginUrl = $helper->getLoginUrl($redirectUrl, $permissions);
        return $this->redirect($loginUrl);
    }

    /**
     * This is the URL that Facebook will redirect back to after the user approves/denies access
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
        $facebook = $this->createFacebook();


        $helper = $facebook->getRedirectLoginHelper();
        $accessToken = $helper->getAccessToken();
//        $userId = $facebook->getOAuth2Client()->debugToken($accessToken)->getUserId();
        $userId = $accessToken->getValue();
        if(!$userId) die('no user id');


//        $helper = $facebook->getRedirectLoginHelper();
//
//        try {
//            $accessToken = $helper->getAccessToken();
//
//        } catch(FacebookResponseException $e) {
//            // When Graph returns an error
//            echo 'Graph returned an error: ' . $e->getMessage();
//            exit;
//        } catch(FacebookSDKException $e) {
//            // When validation fails or other local issues
//            echo 'Facebook SDK returned an error: ' . $e->getMessage();
//            exit;
//        }
//
//        if (! isset($accessToken)) {
//            if ($helper->getError()) {
//                header('HTTP/1.0 401 Unauthorized');
//                echo "Error: " . $helper->getError() . "\n";
//                echo "Error Code: " . $helper->getErrorCode() . "\n";
//                echo "Error Reason: " . $helper->getErrorReason() . "\n";
//                echo "Error Description: " . $helper->getErrorDescription() . "\n";
//            } else {
//                header('HTTP/1.0 400 Bad Request');
//                echo 'Bad request';
//            }
//            exit;
//        }

        $user = $this->getLoggedInUser();
        $user->facebookUserId = $userId;
        $this->saveUser($user);
        return $this->redirect($this->generateUrl('home'));


    }

    /**
     * Posts your current status to your Facebook wall then redirects to
     * the homepage.
     *
     * @return RedirectResponse
     */
    public function shareProgressOnFacebook()
    {



        $user = $this->getLoggedInUser();
        $facebook = $this->createFacebook();

        $response = $facebook->get('/me?fields=id,name',  $user->facebookUserId);
        $userFb = $response->getGraphUser();
        $userid = $userFb->getId();
        $response = $facebook->post(
            '/me/feed',
            array (
                'message' => 'This is a test message',
            ),
            $user->facebookUserId
        );
        die('ici');
        var_dump($response);die;
        return $this->redirect($this->generateUrl('home'));
    }

    public function createFacebook()
    {
        $config = array(
            'app_id' => '559496897815237',
            'app_secret' => 'f4fc8ca92e48634a2cd422fd85206a3a',
            'default_graph_version' => 'v2.10',
        );

        $facebook = new Facebook($config);
        return $facebook;
    }
}
