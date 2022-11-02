<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Http\Client\Common\HttpMethodsClientInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\ImapBundle\Exception\OAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;

/**
 * The base class for OAuth providers.
 */
abstract class AbstractOAuthProvider implements OAuthProviderInterface
{
    private const MAX_RETRY_ATTEMPTS = 3;

    private HttpMethodsClientInterface $httpClient;
    private ResourceOwnerMap $resourceOwnerMap;

    public function __construct(
        HttpMethodsClientInterface $httpClient,
        ResourceOwnerMap $resourceOwnerMap
    ) {
        $this->httpClient = $httpClient;
        $this->resourceOwnerMap = $resourceOwnerMap;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessTokenByAuthCode(string $code, array $scopes = null): OAuthAccessTokenData
    {
        $response = $this->doAccessTokenHttpRequest($this->getAccessTokenParameters($code, $scopes));
        if (empty($response['access_token'])) {
            throw new OAuthAccessTokenFailureException($this->getFailureReason($response), $code);
        }

        return $this->createAccessTokenData($response);
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessTokenByRefreshToken(string $refreshToken, array $scopes = null): OAuthAccessTokenData
    {
        $response = $this->doAccessTokenHttpRequest($this->getRefreshTokenParameters($refreshToken, $scopes));
        if (empty($response['access_token'])) {
            throw new RefreshOAuthAccessTokenFailureException($this->getFailureReason($response), $refreshToken);
        }

        return $this->createAccessTokenData($response);
    }

    /**
     * {@inheritDoc}
     */
    public function getUserInfo(string $accessToken): UserResponseInterface
    {
        $resourceOwner = $this->resourceOwnerMap->getResourceOwnerByName($this->getResourceOwnerName());
        if (null === $resourceOwner) {
            throw new \LogicException(sprintf(
                'The resource owner "%s" does not exist.',
                $this->getResourceOwnerName()
            ));
        }

        return $resourceOwner->getUserInformation(['access_token' => $accessToken]);
    }

    /**
     * Gets the URL to where access token requests should be sent.
     */
    abstract protected function getAccessTokenUrl(): string;

    /**
     * Gets the name of the resource owner.
     */
    abstract protected function getResourceOwnerName(): string;

    /**
     * Gets parameters common for all types of requests.
     */
    abstract protected function getCommonParameters(): array;

    /**
     * Gets parameters for a request to exchange the authorization code to the access token.
     */
    protected function getAccessTokenParameters(string $code, array $scopes = null): array
    {
        return [
            'grant_type' => 'authorization_code',
            'code'       => $code
        ];
    }

    /**
     * Gets parameters for a request to renew the access token via the refresh token.
     */
    protected function getRefreshTokenParameters(string $refreshToken, array $scopes = null): array
    {
        $parameters = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken
        ];
        if (null !== $scopes && !empty($scopes)) {
            $parameters['scope'] = implode(' ', $scopes);
        }

        return $parameters;
    }

    private function doHttpRequest(array $parameters): array
    {
        $content = http_build_query(array_merge($this->getCommonParameters(), $parameters));
        $headers = [
            'Content-length' => \strlen($content),
            'content-type'   => 'application/x-www-form-urlencoded',
            'user-agent'     => 'oro-oauth'
        ];

        $response = $this->httpClient->post($this->getAccessTokenUrl(), $headers, $content);
        $responseBody = $response->getBody();
        if (!$responseBody) {
            return [];
        }

        return json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
    }

    private function doAccessTokenHttpRequest(array $parameters): array
    {
        $attemptNumber = 0;
        do {
            $response = $this->doHttpRequest($parameters);
            $attemptNumber++;
        } while ($attemptNumber <= self::MAX_RETRY_ATTEMPTS && empty($response['access_token']));

        return $response;
    }

    private function getFailureReason(array $response): string
    {
        $failureReason = [];
        if (!empty($response['error'])) {
            $failureReason[] = $response['error'];
        }
        if (!empty($response['error_description'])) {
            $failureReason[] = sprintf('(%s)', $response['error_description']);
        }

        return implode(' ', $failureReason);
    }

    private function createAccessTokenData(array $response): OAuthAccessTokenData
    {
        return new OAuthAccessTokenData(
            $response['access_token'],
            empty($response['refresh_token']) ? null : $response['refresh_token'],
            empty($response['expires_in']) ? null : (int)$response['expires_in']
        );
    }
}
