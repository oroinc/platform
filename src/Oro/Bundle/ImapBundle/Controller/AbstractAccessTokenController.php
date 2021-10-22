<?php

namespace Oro\Bundle\ImapBundle\Controller;

use HWI\Bundle\OAuthBundle\OAuth\Exception\HttpTransportException;
use Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The base class for controllers to receive OAuth access token.
 */
abstract class AbstractAccessTokenController extends AbstractController
{
    public function accessTokenAction(Request $request): Response
    {
        $code = $request->get('code');
        if (!$code) {
            return new JsonResponse([
                'error' => $request->get('error_description') ?: $this->trans('oro.imap.oauth.manager.token.error')
            ]);
        }

        $scopes = null;
        $state = $request->get('state');
        if ($state) {
            $scopes = $this->getAccessTokenScopes($this->decodeState($state));
        }

        try {
            $response = $this->getAccessToken($code, $scopes);
        } catch (\Exception $e) {
            $response = ['error' => $e->getMessage()];
        }

        return new JsonResponse($response);
    }

    abstract protected function getOAuthProvider(): OAuthProviderInterface;

    protected function trans(string $id): string
    {
        return $this->get(TranslatorInterface::class)->trans($id);
    }

    protected function getAccessTokenScopes(string $state): ?array
    {
        return null;
    }

    private function getAccessToken(string $code, ?array $scopes): array
    {
        $oauthProvider = $this->getOAuthProvider();
        $accessTokenData = $oauthProvider->getAccessTokenByAuthCode($code, $scopes);
        try {
            $response = [
                'access_token' => $accessTokenData->getAccessToken(),
                'refresh_token' => $accessTokenData->getRefreshToken(),
                'expires_in' => $accessTokenData->getExpiresIn(),
                'email_address' => $oauthProvider->getUserInfo($accessTokenData->getAccessToken())->getEmail()
            ];
        } catch (HttpTransportException $exc) {
            $response = [ 'error' => $exc->getMessage() ];
        }

        return $response;
    }

    private function decodeState(string $state): string
    {
        $decoded = base64_decode($state, true);

        return false !== $decoded ? $decoded: $state;
    }

    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
            ]
        );
    }
}
