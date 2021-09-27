<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Client;

use Behat\Mink\Session;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;

/**
 * Probably it should be defined as a service - just for behat.
 * Also ClientInterface can be passed as a dependency in __construct()
 */
class FileDownloader
{
    use AssertTrait;

    /**
     * @param string  $url
     * @param string  $filePath Full file path where file content should be saved to
     * @param Session $session
     * @return bool
     */
    public function download($url, $filePath, Session $session)
    {
        $cookieJar = $this->getCookieJar($session, $url);
        $client = new Client(['base_uri' => $session->getCurrentUrl()]);
        $response = $client->get($url, ['sink' => $filePath, 'cookies' => $cookieJar]);

        self::assertEquals(200, $response->getStatusCode());

        return true;
    }

    /**
     * @param Session $session
     *
     * @param string  $url
     * @return CookieJar
     */
    private function getCookieJar(Session $session, string $url)
    {
        $cookies = $session->getDriver()->getWebDriverSession()->getAllCookies();
        $cookieJar = new CookieJar(true, $cookies);
        foreach ($cookies as $cookieData) {
            $data = [];
            if (isset($cookieData['sameSite'])) {
                // Sets SameSite via $data constructor argument because setSameSite() is not present in SetCookie.
                $data['SameSite'] = $cookieData['sameSite'];
                unset($cookieData['sameSite']);
            }
            $setCookie = new SetCookie($data);
            foreach ($cookieData as $name => $value) {
                if ($name === 'expiry') {
                    # The "expiry" option in Selenium matches "expires" option in Guzzle
                    $name = 'expires';
                }
                # Property names in Selenium cookie start with a lowercase character, but they start with the uppercase
                # character in Guzzle, so we can't pass the Selenium cookie to the \GuzzleHttp\Cookie\SetCookie
                # constructor, and calling setters is more reliable.
                call_user_func([$setCookie, 'set'.ucfirst($name)], $value);
            }
            $cookieJar->setCookie($setCookie);
        }

        return $cookieJar;
    }
}
