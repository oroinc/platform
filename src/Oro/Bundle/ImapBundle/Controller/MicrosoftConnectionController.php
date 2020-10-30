<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Manager\ImapEmailMicrosoftOauth2Manager;
use Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides check-connection and access token endpoints for OAuth
 * authentication and related services. Integration for Microsoft
 * Azure application to support OAuth for IMAP/SMTP.
 *
 * @Route("/microsoft-identity")
 */
class MicrosoftConnectionController extends AbstractVendorConnectionController
{
    private const KEY_ACCESS_TOKEN = '_access_token_data';

    /**
     * @Route("/connection/check", name="oro_imap_microsoft_connection_check", methods={"POST"})
     * @CsrfProtection()
     */
    public function checkAction()
    {
        return $this->check($this->getRequestStack()->getCurrentRequest());
    }

    /**
     * @Route("/connection/access-token", name="oro_imap_microsoft_access_token", methods={"POST", "GET"})
     */
    public function accessTokenAction()
    {
        if (!$this->getRequestStack()->getCurrentRequest()->isXmlHttpRequest()) {
            $response = $this->handleAccessToken(
                $this->getRequestStack()->getCurrentRequest(),
                AccountTypeModel::ACCOUNT_TYPE_MICROSOFT
            );

            return $this->storeResponse($response);
        } else {
            return $this->restoreResponse();
        }
    }

    /**
     * Stores response in session for further calls
     * from component
     *
     * @param Response $response
     * @return Response
     */
    private function storeResponse(Response $response): Response
    {
        /** @var SessionInterface $session */
        $session = $this->container->get('session');
        $session->set(self::KEY_ACCESS_TOKEN, $response->getContent());

        $response = new Response();
        $response->setContent('');
        $response->setStatusCode(200);

        return $response;
    }

    /**
     * Returns response instance provided from session stored data
     *
     * @return Response|JsonResponse
     */
    private function restoreResponse(): Response
    {
        /** @var SessionInterface $session */
        $session = $this->container->get('session');
        $token = $session->get(self::KEY_ACCESS_TOKEN);
        $response = json_decode($token, true);
        if (!$token || !$response || !array_key_exists('refresh_token', $response)) {
            $error = $this->container->get('translator')->trans('oro.imap.oauth.manager.microsoft.error.token');
            $response = [
                'error' => $error
            ];
        } else {
            $session->remove(self::KEY_ACCESS_TOKEN);

            /*
             * Microsoft 365 API does not allow multi-scope calls.
             * First call contain access token used for email access ONLY.
             * To allow profile data to be fetched, another call needs to be
             * performed to the OAuth endpoint to generate an extra token for
             * profile access ONLY.
             */
            $response = $this->appendProfileData($response);
        }

        return new JsonResponse($response);
    }

    /**
     * @param array $originalResponse
     * @return array
     */
    private function appendProfileData(array $originalResponse): array
    {
        /** @var Oauth2ManagerInterface $manager */
        $manager = $this
            ->container
            ->get('oro_imap.manager_registry.registry')
            ->getManager(AccountTypeModel::ACCOUNT_TYPE_MICROSOFT);

        $accessTokenData = $manager
            ->setScopes(ImapEmailMicrosoftOauth2Manager::OAUTH2_OFFICE365_USER_SCOPES)
            ->getAccessTokenDataByRefreshToken($originalResponse['refresh_token']);

        $userInfo = $manager->getUserInfo($accessTokenData);
        $newResponse = array_merge($originalResponse, [
            'email_address' => $userInfo->getEmail()
        ]);

        return array_merge($newResponse);
    }
}
