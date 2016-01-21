<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Buzz\Message\MessageInterface;
use Buzz\Client\ClientInterface;
use Buzz\Client\Curl;
use Buzz\Message\Request;
use Buzz\Message\RequestInterface;
use Buzz\Message\Response;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class ImapEmailGoogleOauth2Manager
{
    const OAUTH2_ACCESS_TOKEN_URL = 'https://www.googleapis.com/oauth2/v4/token';
    const OAUTH2_GMAIL_SCOPE = 'https://mail.google.com/';
    const RETRY_TIMES = 3;

    /** @var Curl */
    protected $httpClient;

    /** @var ConfigManager */
    protected $configManager;

    /** @var Registry */
    private $doctrine;

    /**
     * @param ClientInterface $httpClient
     * @param ConfigManager $configManager
     * @param Registry $doctrine
     */
    public function __construct(
        ClientInterface $httpClient,
        ConfigManager $configManager,
        Registry $doctrine
    ) {
        $this->httpClient = $httpClient;
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
     * @param UserEmailOrigin $origin
     *
     * @return string
     */
    public function getAccessTokenWithCheckingExpiration(UserEmailOrigin $origin)
    {
        $expiresAt = $origin->getAccessTokenExpiresAt();
        $utcTimeZone = new \DateTimeZone('UTC');
        $now = new \DateTime('now', $utcTimeZone);

        $token = $origin->getAccessToken();

        //if token had been expired, the new one must be generated and saved to DB
        if ($now > $expiresAt) {
            $parameters = [
                'refresh_token' => $origin->getRefreshToken(),
                'grant_type'    => 'refresh_token'
            ];

            $attemptNumber = 0;
            do {
                $attemptNumber++;
                $response = $this->doHttpRequest($parameters);

                if (!empty($response['access_token'])) {
                    $token = $response['access_token'];
                    $origin->setAccessToken($token);
                    $newExpireDate = new \DateTime('+' . $response['expires_in'] . ' seconds', $utcTimeZone);
                    $origin->setAccessTokenExpiresAt($newExpireDate);

                    $this->doctrine->getManager()->persist($origin);
                    $this->doctrine->getManager()->flush();
                }
            } while ($attemptNumber <= self::RETRY_TIMES && empty($response['access_token']));
        }

        return $token;
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
