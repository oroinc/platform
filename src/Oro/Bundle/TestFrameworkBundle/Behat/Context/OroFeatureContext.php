<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\Mink\Session;
use Behat\MinkExtension\Context\RawMinkContext;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Basic feature context which may be used as parent class for other contexts.
 * Provides assert and spin functions.
 */
class OroFeatureContext extends RawMinkContext implements AppKernelAwareInterface
{
    use AssertTrait;
    use SpinTrait;
    use AppKernelAwareTrait;

    public function waitForAjax()
    {
        $this->getDriver()->waitForAjax();
    }

    /**
     * {@inheritdoc}
     */
    public function getSession($name = null)
    {
        $session = parent::getSession($name);

        // start session if needed
        if (!$session->isStarted()) {
            $session->start();
        }

        return $session;
    }

    /**
     * @return OroSelenium2Driver
     */
    protected function getDriver()
    {
        return $this->getSession()->getDriver();
    }

    /**
     * Returns fixed step argument (\\" replaced back to ", \\# replaced back to #)
     *
     * @param string $argument
     *
     * @return string
     */
    protected function fixStepArgument($argument)
    {
        return str_replace(['\\"', '\\#'], ['"', '#'], $argument);
    }

    /**
     * @param int|string $count
     * @return int
     */
    protected function getCount($count)
    {
        switch (trim($count)) {
            case '':
                return 1;
            case 'one':
                return 1;
            case 'two':
                return 2;
            default:
                return (int) $count;
        }
    }

    /**
     * @param Session $session
     *
     * @return CookieJar
     */
    protected function getCookieJar(Session $session)
    {
        $sessionCookies = $session->getDriver()->getWebDriverSession()->getCookie();
        $cookies = [];
        foreach ($sessionCookies as $sessionCookie) {
            $cookie = [];
            foreach ($sessionCookie as $key => $value) {
                $cookie[ucwords($key, '-')] = $value;
            }
            $cookies[] = $cookie;
        }

        return new CookieJar(false, $cookies);
    }

    protected function loadImage(string $imageUrl, bool|array $allowRedirects = false): ResponseInterface
    {
        $imageUrl = $this->locatePath($imageUrl);
        $imageUrl = filter_var($imageUrl, FILTER_VALIDATE_URL);

        self::assertIsString($imageUrl, sprintf('Image src "%s" is not valid', $imageUrl));

        $cookieJar = $this->getCookieJar($this->getSession());
        $client = new Client([
            'allow_redirects' => $allowRedirects,
            'cookies' => $cookieJar,
        ]);

        return $client->get($imageUrl);
    }

    protected function copyFiles(string $sourcePath, string $destinationPath): void
    {
        $fs = new Filesystem();
        try {
            $isDir = str_ends_with($destinationPath, '/');
            $destinationPath = rtrim($destinationPath, '/');
            if ($isDir && !$fs->exists($destinationPath)) {
                $fs->mkdir($destinationPath);
            }

            if (is_dir($sourcePath)) {
                $fs->mirror($sourcePath, $destinationPath);
            } else {
                if ($isDir) {
                    $filename = basename($sourcePath);
                    $destinationPath = sprintf('%s/%s', $destinationPath, $filename);
                }
                $fs->copy($sourcePath, $destinationPath);
            }
        } catch (\Throwable $e) {
            $this->fail(
                sprintf(
                    'Failed to copy fixture files from %s to %s: %s',
                    $sourcePath,
                    $destinationPath,
                    $e->getMessage()
                )
            );
        }
    }
}
