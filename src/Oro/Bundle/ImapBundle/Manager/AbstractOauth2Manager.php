<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Http\Client\Common\HttpMethodsClientInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\PathUserResponse;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Manager\DTO\TokenInfo;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Http\Message\MessageInterface;

/**
 * Default common abstractions for concrete implementations of
 * Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface
 */
abstract class AbstractOauth2Manager implements Oauth2ManagerInterface
{
    public const RETRY_TIMES = 3;

    protected const AUTH_MODE = 'XOAUTH2';

    /** @var HttpMethodsClientInterface */
    protected $httpClient;

    /** @var ResourceOwnerMap */
    protected $resourceOwnerMap;

    /** @var ConfigManager */
    protected $configManager;

    /** @var OAuth2ManagerRegistry */
    protected $doctrine;

    /** @var string */
    protected $accessTokenUrl;

    /** @var array|string */
    protected $scopes;

    /** @var SymmetricCrypterInterface */
    protected $crypter;

    /**
     * @param HttpMethodsClientInterface $httpClient
     * @param ResourceOwnerMap $resourceOwnerMap
     * @param ConfigManager $configManager
     * @param OAuth2ManagerRegistry $doctrine
     */
    public function __construct(
        HttpMethodsClientInterface $httpClient,
        ResourceOwnerMap $resourceOwnerMap,
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        SymmetricCrypterInterface $crypter
    ) {
        $this->httpClient = $httpClient;
        $this->resourceOwnerMap = $resourceOwnerMap;
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
        $this->crypter = $crypter;
    }

    /**
     * @param string $accessTokenUrl
     * @return self
     */
    public function setAccessTokenUrl(string $accessTokenUrl): self
    {
        $this->accessTokenUrl = $accessTokenUrl;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessTokenByAuthCode($code)
    {
        $parameters = $this->buildParameters($code);

        $attemptNumber = 0;
        do {
            $attemptNumber++;
            $response = $this->doHttpRequest($parameters);

            $result = [
                'access_token' => empty($response['access_token']) ? '' : $response['access_token'],
                'refresh_token' => empty($response['refresh_token']) ? '' : $response['refresh_token'],
                'expires_in' => empty($response['expires_in']) ? '' : $response['expires_in']
            ];
        } while ($attemptNumber <= self::RETRY_TIMES && empty($result['access_token']));

        return $result;
    }

    /**
     * {@InheritDoc}
     */
    public function getAccessTokenDataByAuthCode(string $code): TokenInfo
    {
        return new TokenInfo($this->getAccessTokenByAuthCode($code));
    }

    /**
     * {@inheritDoc}
     *
     * @return PathUserResponse|UserResponseInterface
     */
    public function getUserInfo(TokenInfo $tokenInfo)
    {
        $resourceOwner = $this->resourceOwnerMap->getResourceOwnerByName($this->getResourceOwnerName());
        /** @var PathUserResponse $response */
        $response = $resourceOwner->getUserInformation(['access_token' => $tokenInfo->getAccessToken()]);

        return $response;
    }

    /**
     * {@inheritDoc}
     *
     * @throws ORMException
     */
    public function getAccessTokenWithCheckingExpiration(UserEmailOrigin $origin)
    {
        $token = $origin->getAccessToken();

        //if token had been expired, the new one must be generated and saved to DB
        if ($this->isAccessTokenExpired($origin)
            && $this->isOauthEnabled()
            && $origin->getRefreshToken()
        ) {
            $this->refreshAccessToken($origin);

            /** @var EntityManager $em */
            $em = $this->doctrine->getManagerForClass(ClassUtils::getClass($origin));
            $em->persist($origin);
            $em->flush($origin);

            $token = $origin->getAccessToken();
        }

        return $token;
    }

    /**
     * {@inheritDoc}
     */
    public function isAccessTokenExpired(UserEmailOrigin $origin)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return $now > $origin->getAccessTokenExpiresAt();
    }

    /**
     * {@inheritDoc}
     */
    public function refreshAccessToken(UserEmailOrigin $origin)
    {
        $refreshToken = $origin->getRefreshToken();
        if (empty($refreshToken)) {
            throw new RefreshOAuthAccessTokenFailureException('The RefreshToken is empty', $refreshToken);
        }

        $response = $this->getAccessTokenDataByRefreshToken($refreshToken);

        $origin->setAccessToken($response->getAccessToken());
        $origin->setAccessTokenExpiresAt(new \DateTime(
            '+' . ((int)$response->getExpiresIn() - 5) . ' seconds',
            new \DateTimeZone('UTC')
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessTokenDataByRefreshToken(string $refreshToken): TokenInfo
    {
        $parameters = $this->getRefreshTokenParameters($refreshToken);

        $response = [];
        $attemptNumber = 0;
        while ($attemptNumber <= self::RETRY_TIMES && empty($response['access_token'])) {
            $response = $this->doHttpRequest($parameters);
            $attemptNumber++;
        }

        if (empty($response['access_token'])) {
            throw new RefreshOAuthAccessTokenFailureException($this->getFailureReason($response), $refreshToken);
        }

        return new TokenInfo($response);
    }

    /**
     * @param string $refreshToken
     * @return string[]
     */
    protected function getRefreshTokenParameters(string $refreshToken): array
    {
        return [
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token'
        ];
    }

    /**
     * @param array $response
     * @return string
     */
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

    /**
     * @param array $parameters
     *
     * @return array
     */
    protected function doHttpRequest($parameters)
    {
        $contentParameters = $this->getConfigParameters();
        $parameters = array_merge($contentParameters, $parameters);
        $content = http_build_query($parameters, '', '&');
        $headers = [
            'Content-length' => strlen($content),
            'content-type' => 'application/x-www-form-urlencoded',
            'user-agent' => 'oro-oauth'
        ];

        $response = $this->httpClient->post($this->getAccessTokenUrl(), $headers, $content);

        return $this->getResponseContent($response);
    }

    /**
     * Returns access token URL for HTTP request
     *
     * @return string
     */
    protected function getAccessTokenUrl(): string
    {
        return $this->accessTokenUrl;
    }

    /**
     * Get the 'parsed' content based on the response headers.
     *
     * @param MessageInterface $rawResponse
     *
     * @return array
     */
    protected function getResponseContent(MessageInterface $rawResponse)
    {
        $content = $rawResponse->getBody();
        if (!$content) {
            return [];
        }

        return json_decode($content, true);
    }

    /**
     * @param array $scopes
     * @return AbstractOauth2Manager
     */
    public function setScopes(array $scopes)
    {
        $this->scopes = $scopes;
        return $this;
    }

    /**
     * Returns scope string
     *
     * @return string
     */
    protected function getScope(): string
    {
        return implode(' ', $this->scopes);
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthMode(): string
    {
        return self::AUTH_MODE;
    }

    /**
     * Returns request parameters
     *
     * @param string $code
     * @return array
     */
    abstract protected function buildParameters(string $code): array;

    /**
     * Returns resource owner name
     *
     * @return string
     */
    abstract protected function getResourceOwnerName(): string;

    /**
     * Provides configuration parameters for request
     *
     * @return array
     */
    abstract protected function getConfigParameters(): array;
}
