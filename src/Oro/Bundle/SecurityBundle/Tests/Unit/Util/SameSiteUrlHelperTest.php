<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Util;

use Oro\Bundle\SecurityBundle\Util\SameSiteUrlHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SameSiteUrlHelperTest extends \PHPUnit\Framework\TestCase
{
    private RequestStack $requestStack;

    private SameSiteUrlHelper $sameSiteUrlHelper;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->sameSiteUrlHelper = new SameSiteUrlHelper($this->requestStack);
    }

    /**
     * @dataProvider getSameSiteRefererDataProvider
     */
    public function testGetSameSiteReferer(Request $request, string $fallbackUrl, string $expected): void
    {
        self::assertEquals($expected, $this->sameSiteUrlHelper->getSameSiteReferer($request, $fallbackUrl));
    }

    /**
     * @dataProvider getSameSiteRefererDataProvider
     */
    public function testGetSameSiteRefererWhenNoRequest(Request $request, string $fallbackUrl, string $expected): void
    {
        $this->requestStack->push($request);

        self::assertEquals($expected, $this->sameSiteUrlHelper->getSameSiteReferer(null, $fallbackUrl));
    }

    public function getSameSiteRefererDataProvider(): array
    {
        return [
            'empty request' => [
                'request' => new Request(),
                'fallbackUrl' => '',
                'expected' => '',
            ],
            'no referer' => [
                'request' => Request::create('http://example.org'),
                'fallbackUrl' => '',
                'expected' => '',
            ],
            'no referer with fallback url' => [
                'request' => Request::create('http://example.org'),
                'fallbackUrl' => 'http://example.org/home',
                'expected' => 'http://example.org/home',
            ],
            'different host' => [
                'request' => Request::create(
                    'http://example.org',
                    'GET',
                    [],
                    [],
                    [],
                    ['HTTP_REFERER' => 'http://google.com']
                ),
                'fallbackUrl' => 'http://example.org/home',
                'expected' => 'http://example.org/home',
            ],
            'different port' => [
                'request' => Request::create(
                    'http://example.org',
                    'GET',
                    [],
                    [],
                    [],
                    ['HTTP_REFERER' => 'http://example.org:8080']
                ),
                'fallbackUrl' => 'http://example.org/home',
                'expected' => 'http://example.org/home',
            ],
            'different scheme' => [
                'request' => Request::create(
                    'https://example.org',
                    'GET',
                    [],
                    [],
                    [],
                    ['HTTP_REFERER' => 'http://example.org']
                ),
                'fallbackUrl' => 'https://example.org/home',
                'expected' => 'https://example.org/home',
            ],
            'js scheme' => [
                'request' => Request::create(
                    'https://example.org',
                    'GET',
                    [],
                    [],
                    [],
                    ['HTTP_REFERER' => 'javascript:alert(1);']
                ),
                'fallbackUrl' => 'https://example.org/home',
                'expected' => 'https://example.org/home',
            ],
            'js scheme when on http' => [
                'request' => Request::create(
                    'http://example.org',
                    'GET',
                    [],
                    [],
                    [],
                    ['HTTP_REFERER' => 'javascript:alert(1);']
                ),
                'fallbackUrl' => 'http://example.org/home',
                'expected' => 'http://example.org/home',
            ],
        ];
    }
}
