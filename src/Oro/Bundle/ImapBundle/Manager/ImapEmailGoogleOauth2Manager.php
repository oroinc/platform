<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Buzz\Client\ClientInterface;
use Buzz\Client\Curl;
use Buzz\Message\MessageInterface;
use Buzz\Message\Request;
use Buzz\Message\RequestInterface;
use Buzz\Message\Response;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use HWI\Bundle\OAuthBundle\OAuth\Response\PathUserResponse;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;

class ImapEmailGoogleOauth2Manager
{
    const OAUTH2_ACCESS_TOKEN_URL = 'https://www.googleapis.com/oauth2/v4/token';
    const OAUTH2_GMAIL_SCOPE = 'https://mail.google.com/';
    const RETRY_TIMES = 3;
    const RESOURCE_OWNER_GOOGLE = 'google';

    /** @var Curl */
    protected $httpClient;

    /** @var ResourceOwnerMap */
    protected $resourceOwnerMap;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ManagerRegistry */
    private $doctrine;

    /**
     * @param ClientInterface $httpClient
     * @param ResourceOwnerMap $resourceOwnerMap
     * @param ConfigManager $configManager
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        ClientInterface $httpClient,
        ResourceOwnerMap $resourceOwnerMap,
        ConfigManager $configManager,
        ManagerRegistry $doctrine
    ) {
        $this->httpClient = $httpClient;
        $this->resourceOwnerMap = $resourceOwnerMap;
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
    }

    /**
     * @param string $code
     *
     * @return array
     */
    public function getAccessTokenByAuthCode($code)
    {
        $parameters = [
            'redirect_uri' => 'postmessage',
            'scope' => self::OAUTH2_GMAIL_SCOPE,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];

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
     * @param string $accessToken
     *
     * @return PathUserResponse
     */
    public function getUserInfo($accessToken)
    {
        $resourceOwner = $this->resourceOwnerMap->getResourceOwnerByName(self::RESOURCE_OWNER_GOOGLE);

        return $resourceOwner->getUserInformation(['access_token' => $accessToken]);
    }

    /**
     * @param UserEmailOrigin $origin
     *
     * @return string
     *
     * @deprecated since 1.10. Use refreshAccessToken or getAccessTokenWithCheckingExpiration
     */
    public function getAccessToken(UserEmailOrigin $origin)
    {
        $this->refreshAccessToken($origin);

        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass(ClassUtils::getClass($origin));
        $em->persist($origin);
        $em->flush();

        return $origin->getAccessToken();
    }

    /**
     * @param UserEmailOrigin $origin
     *
     * @return string
     */
    public function getAccessTokenWithCheckingExpiration(UserEmailOrigin $origin)
    {
        $token = $origin->getAccessToken();

        //if token had been expired, the new one must be generated and saved to DB
        if ($this->isAccessTokenExpired($origin)
            && $this->configManager->get('oro_imap.enable_google_imap')
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
     * @param UserEmailOrigin $origin
     *
     * @return bool
     */
    public function isAccessTokenExpired(UserEmailOrigin $origin)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return $now > $origin->getAccessTokenExpiresAt();
    }

    /**
     * @param UserEmailOrigin $origin
     *
     * @throws RefreshOAuthAccessTokenFailureException
     */
    public function refreshAccessToken(UserEmailOrigin $origin)
    {
        $refreshToken = $origin->getRefreshToken();
        if (empty($refreshToken)) {
            throw new RefreshOAuthAccessTokenFailureException('The RefreshToken is empty', $refreshToken);
        }

        $parameters = [
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token'
        ];

        $response = [];
        $attemptNumber = 0;
        while ($attemptNumber <= self::RETRY_TIMES && empty($response['access_token'])) {
            $response = $this->doHttpRequest($parameters);
            $attemptNumber++;
        }

        if (empty($response['access_token'])) {
            $failureReason = '';
            if (!empty($response['error'])) {
                $failureReason .= $response['error'];
            }
            if (!empty($response['error_description'])) {
                $failureReason .= sprintf(' (%s)', $response['error_description']);
            }

            throw new RefreshOAuthAccessTokenFailureException($failureReason, $refreshToken);
        }

        $origin->setAccessToken($response['access_token']);
        $origin->setAccessTokenExpiresAt(
            new \DateTime('+' . ((int)$response['expires_in'] - 5) . ' seconds', new \DateTimeZone('UTC'))
        );
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    protected function doHttpRequest($parameters)
    {
        $request = new Request(RequestInterface::METHOD_POST, self::OAUTH2_ACCESS_TOKEN_URL);
        $response = new Response();

        $contentParameters = [
            'client_id'     => $this->configManager->get('oro_google_integration.client_id'),
            'client_secret' => $this->configManager->get('oro_google_integration.client_secret'),
        ];

        $parameters = array_merge($contentParameters, $parameters);
        $content = http_build_query($parameters, '', '&');
        $headers = [
            'Content-length: ' . strlen($content),
            'content-type: application/x-www-form-urlencoded',
            'user-agent: oro-oauth'
        ];

        $request->setHeaders($headers);
        $request->setContent($content);

        $this->httpClient->send($request, $response);

        return $this->getResponseContent($response);
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
        $content = $rawResponse->getContent();
        if (!$content) {
            return [];
        }

        return json_decode($content, true);
    }
}
