<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Client;

use Behat\Mink\Session;

use Guzzle\Http\Client;
use Guzzle\Plugin\Cookie\Cookie;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\Cookie\CookiePlugin;

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
        $cookies = $session->getDriver()->getWebDriverSession()->getCookie()[0];

        $cookie = new Cookie();
        $cookie->setName($cookies['name']);
        $cookie->setValue($cookies['value']);
        $cookie->setDomain($cookies['domain']);

        $jar = new ArrayCookieJar();
        $jar->add($cookie);

        $client = new Client($session->getCurrentUrl());
        $client->addSubscriber(new CookiePlugin($jar));
        $request = $client->get($url, null, ['save_to' => $filePath]);
        $response = $request->send();

        self::assertEquals(200, $response->getStatusCode());

        return true;
    }
}
