<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\WidgetContextProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WidgetContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack */
    private $requestStack;

    /** @var WidgetContextProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();

        $this->provider = new WidgetContextProvider($this->requestStack);
    }

    /**
     * @dataProvider isActiveProvider
     */
    public function testIsActive(bool $expectedValue, Request $request = null)
    {
        if ($request) {
            $this->requestStack->push($request);
        }

        $this->assertSame($expectedValue, $this->provider->isActive());
    }

    public function isActiveProvider(): array
    {
        return [
            'normal request, should be inactive'                   => [
                '$expectedValue' => false,
                '$request'       => Request::create('')
            ],
            'widget request, should be active'                     => [
                '$expectedValue' => true,
                '$request'       => Request::create('', 'GET', ['_wid' => '123-321-123-321'])
            ],
            'unset request before sub-request, should be inactive' => [
                '$expectedValue' => false,
                '$request'       => null
            ]
        ];
    }

    /**
     * @dataProvider widgetIdentifierProvider
     */
    public function testGetWid(string|bool $expectedValue, Request $request = null)
    {
        if ($request) {
            $this->requestStack->push($request);
        }

        $this->assertSame($expectedValue, $this->provider->getWid());
    }

    public function widgetIdentifierProvider(): array
    {
        return [
            'normal request, should return false'                   => [
                '$expectedValue' => false,
                '$request'       => Request::create('')
            ],
            'widget request, should return wid'                     => [
                '$expectedValue' => '123-321-123-321',
                '$request'       => Request::create('', 'GET', ['_wid' => '123-321-123-321'])
            ],
            'unset request before sub-request, should return false' => [
                '$expectedValue' => false,
                '$request'       => null
            ]
        ];
    }
}
