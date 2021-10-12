<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Oro\Bundle\ImapBundle\Provider\MicrosoftOAuthProvider;
use Oro\Bundle\ImapBundle\Provider\MicrosoftOAuthScopeProvider;
use Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface;
use Oro\Bundle\ImapBundle\Provider\OAuthScopeProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * The controller to receive OAuth access token for Microsoft integration.
 */
class MicrosoftAccessTokenController extends AbstractAccessTokenController
{
    private const ACCESS_TOKEN_DATA_SESSION_KEY = '_microsoft_access_token_data';

    /**
     * {@inheritDoc}
     */
    public function accessTokenAction(Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            return $this->restoreResponse($request->getSession());
        }

        return $this->storeResponse($request->getSession(), parent::accessTokenAction($request));
    }

    /**
     * {@inheritDoc}
     */
    protected function getOAuthProvider(): OAuthProviderInterface
    {
        return $this->get(MicrosoftOAuthProvider::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function getAccessTokenScopes(string $state): ?array
    {
        return $this->getOAuthScopeProvider()->getAccessTokenScopes($this->getTokenType($state));
    }

    private function getTokenType(string $state): string
    {
        $tokenType = '';
        $tokenPrefix = 'token=';
        $start = strpos($state, $tokenPrefix);
        if (false !== $start && (0 === $start || '&' === $state[$start - 1])) {
            $start += \strlen($tokenPrefix);
            $end = strpos($state, '&', $start);
            if (false !== $end) {
                $tokenType = substr($state, $start, $end - $start);
            }
        }

        return $tokenType;
    }

    private function getOAuthScopeProvider(): OAuthScopeProviderInterface
    {
        return $this->get(MicrosoftOAuthScopeProvider::class);
    }

    private function storeResponse(SessionInterface $session, Response $response): Response
    {
        $session->set(self::ACCESS_TOKEN_DATA_SESSION_KEY, $response->getContent());

        return new Response();
    }

    private function restoreResponse(SessionInterface $session): Response
    {
        $token = $session->get(self::ACCESS_TOKEN_DATA_SESSION_KEY);
        if (null === $token) {
            return new JsonResponse([
                'error' => $this->trans('oro.imap.oauth.manager.microsoft.error.token')
            ]);
        }

        $session->remove(self::ACCESS_TOKEN_DATA_SESSION_KEY);

        $response = json_decode($token, true, 512, JSON_THROW_ON_ERROR);
        if (!\array_key_exists('refresh_token', $response)) {
            return new JsonResponse([
                'error' => $this->trans('oro.imap.oauth.manager.microsoft.error.token')
            ]);
        }

        /**
         * Microsoft identity platform does not allow multi-scope calls.
         * First call contain access token used for email access ONLY.
         * To allow profile data to be fetched, another call needs to be
         * performed to the OAuth endpoint to generate an extra token for
         * profile access ONLY.
         */
        $oauthProvider = $this->getOAuthProvider();
        $accessTokenData = $oauthProvider->getAccessTokenByRefreshToken(
            $response['refresh_token'],
            ['openid', 'offline_access', 'profile', 'User.Read']
        );
        $userInfo = $oauthProvider->getUserInfo($accessTokenData->getAccessToken());
        $response['email_address'] = $userInfo->getEmail();

        return new JsonResponse($response);
    }

    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                MicrosoftOAuthProvider::class,
                MicrosoftOAuthScopeProvider::class,
            ]
        );
    }
}
