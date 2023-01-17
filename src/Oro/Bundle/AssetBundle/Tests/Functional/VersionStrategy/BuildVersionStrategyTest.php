<?php

namespace Oro\Bundle\AssetBundle\Tests\Functional\VersionStrategy;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class BuildVersionStrategyTest extends WebTestCase
{
    public const VERSION_REGEXP_BASE = '[\?|\&]v\=[a-z0-9\-]{8,15}';

    public const VERSION_REGEXP = '/' . self::VERSION_REGEXP_BASE . '(\&|\s+|$)/';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testAdminHeadLinksVersioning()
    {
        $crawler = $this->requestOroDefaultRoute();

        $headLinks = $crawler->filterXPath('//head/link[starts-with(@href, "/")]');
        foreach ($headLinks as $headLink) {
            $headLinkHref = $headLink->getAttribute('href');

            self::assertMatchesRegularExpression(
                self::VERSION_REGEXP,
                $headLinkHref,
                sprintf(
                    "Back-office head link's 'href' is not versioned properly. HRef value: %s",
                    $headLinkHref
                )
            );
        }
    }

    public function testAdminHeadScriptsVersioning()
    {
        $crawler = $this->requestOroDefaultRoute();

        $headScripts = $crawler->filterXPath('//head/script[starts-with(@src, "/")]');
        foreach ($headScripts as $headScript) {
            $headScriptSource = $headScript->getAttribute('src');

            self::assertMatchesRegularExpression(
                self::VERSION_REGEXP,
                $headScriptSource,
                sprintf(
                    'Back-office head script\'s source is not versioned properly. Source value: %s',
                    $headScriptSource
                )
            );
        }
    }

    public function testAdminRoutesJsonVersioning()
    {
        $this->requestOroDefaultRoute();
        $result = $this->client->getResponse();

        self::assertMatchesRegularExpression(
            '/\/(admin_)*routes\.json' . self::VERSION_REGEXP_BASE . '(\&|\s+|\")/',
            $result->getContent()
        );
    }

    public function testAdminTranslationsJsonVersioning()
    {
        $this->requestOroDefaultRoute();
        $result = $this->client->getResponse();

        self::assertMatchesRegularExpression(
            '/\/en\.json' . self::VERSION_REGEXP_BASE . '(\&|\s+|\")/',
            $result->getContent()
        );
    }

    private function requestOroDefaultRoute(): ?Crawler
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_default'));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        
        return $crawler;
    }
}
