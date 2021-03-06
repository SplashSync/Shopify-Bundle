<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Connectors\Shopify\Controller;

use Exception;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Splash\Bundle\Models\AbstractConnector;
use Splash\Bundle\Models\Local\ActionsTrait;
use Splash\Connectors\Shopify\Models\OAuth2Client;
use Splash\Connectors\Shopify\Services\ShopifyConnector;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\Translator;

/**
 * Splash Shopify Connector Actions Controller
 */
class ActionsController extends Controller
{
    use ActionsTrait;

    //==============================================================================
    // OAUTH2 AUTHENTIFICATION
    //==============================================================================

    /**
     * Update User Connector WebHooks List
     *
     * @param Session           $session
     * @param ClientRegistry    $registry
     * @param AbstractConnector $connector
     *
     * @return Response
     */
    public function oauthAction(Session $session, ClientRegistry $registry, AbstractConnector $connector): Response
    {
        //==============================================================================
        // Load Shopify OAuth2 Client
        $client = $registry->getClient("shopify");
        //==============================================================================
        // Safety Check
        if (!($client->getOAuth2Provider() instanceof OAuth2Client)) {
            return self::getDefaultResponse();
        }
        //==============================================================================
        // Configure Shopify OAuth2 Client
        $client->getOAuth2Provider()->configure($connector);
        //==============================================================================
        // Store Connector WebService Id in Session
        $session->set("shopify_oauth2_wsid", $connector->getWebserviceId());
        //==============================================================================
        // Do Shopify OAuth2 Authentification
        return $client->redirect(array(), array());
    }

    /**
     * Register Connector App Token
     *
     * @param Request           $request
     * @param Session           $session
     * @param ClientRegistry    $registry
     * @param AbstractConnector $connector
     *
     * @return Response
     */
    public function registerAction(
        Request $request,
        Session $session,
        ClientRegistry $registry,
        AbstractConnector $connector
    ) {
        //==============================================================================
        // Get Connector WebService Id from Session
        $webserviceId = $session->get("shopify_oauth2_wsid");
        //====================================================================//
        // Perform Identify Pointed Server
        if (false === $connector->identify($webserviceId)) {
            return self::getDefaultResponse();
        }

        //==============================================================================
        // Load Shopify OAuth2 Client
        $client = $registry->getClient("shopify");
        //==============================================================================
        // Safety Check
        if (!($client->getOAuth2Provider() instanceof OAuth2Client)) {
            return self::getDefaultResponse();
        }
        //==============================================================================
        // Configure Shopify OAuth2 Client
        $client->getOAuth2Provider()->configure($connector);
        //==============================================================================
        // Set Client as StateLess
        $client->setAsStateless();

        try {
            //==============================================================================
            // Get Access Token
            $accessToken = $client->getAccessToken();
            //==============================================================================
            // Now update Connector Configuration
            $connector->setParameter("Token", $accessToken->getToken());
            $connector->updateConfiguration();
        } catch (Exception $e) {
            return new Response($e->getMessage(), 400);
        }

        //====================================================================//
        // Redirect Response
        /** @var string $referer */
        $referer = $request->headers->get('referer');
        if (empty($referer)) {
            return self::getDefaultResponse();
        }

        return new RedirectResponse($referer);
    }

    //==============================================================================
    // WEBHOOKS CONFIGURATION
    //==============================================================================

    /**
     * Update User Connector WebHooks List
     *
     * @param Request           $request
     * @param AbstractConnector $connector
     *
     * @return Response
     */
    public function webhooksAction(Request $request, AbstractConnector $connector)
    {
        $result = false;
        //====================================================================//
        // Connector SelfTest
        if (($connector instanceof ShopifyConnector) && $connector->selfTest()) {
            /** @var RouterInterface $router */
            $router = $this->get('router');
            //====================================================================//
            // Update WebHooks Config
            $result = $connector->updateWebHooks($router);
        }
        //====================================================================//
        // Inform User
        /** @var Translator $translator */
        $translator = $this->get('translator');
        $this->addFlash(
            $result ? "success" : "danger",
            $translator->trans(
                $result ? "admin.webhooks.msg" : "admin.webhooks.err",
                array(),
                "ShopifyBundle"
            )
        );
        //====================================================================//
        // Redirect Response
        /** @var string $referer */
        $referer = $request->headers->get('referer');
        if (empty($referer)) {
            return self::getDefaultResponse();
        }

        return new RedirectResponse($referer);
    }
}
