<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Tools;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Symfony\Component\Routing\RouterInterface;

class DatagridRouteHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var DatagridRouteHelper */
    private $routeHelper;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);

        $this->routeHelper = new DatagridRouteHelper($this->router);
    }

    public function testGenerate()
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'routeName',
                ['grid' => ['gridName' => http_build_query(['param1' => 'value1'])]],
                RouterInterface::ABSOLUTE_URL
            )
            ->willReturn('generatedValue');

        $this->assertEquals(
            'generatedValue',
            $this->routeHelper->generate('routeName', 'gridName', ['param1' => 'value1'], RouterInterface::ABSOLUTE_URL)
        );
    }

    /**
     * @dataProvider appendGridParamsDataProvider
     */
    public function testAppendGridParams(string $url, string $gridName, array $gridParams, string $expectedUrl): void
    {
        $this->assertEquals($url, $this->routeHelper->appendGridParams($url, '', []));
        $this->assertEquals($expectedUrl, $this->routeHelper->appendGridParams($url, $gridName, $gridParams));
    }

    public function appendGridParamsDataProvider(): array
    {
        return [
            'empty url' => [
                'url' => '',
                'gridName' => '',
                'gridParams' => [],
                'expectedUrl' => '',
            ],
            'empty gridParams' => [
                'url' => 'sample/url',
                'gridName' => '',
                'gridParams' => [],
                'expectedUrl' => 'sample/url',
            ],
            'empty url with grid params' => [
                'url' => '',
                'gridName' => 'sample_grid',
                'gridParams' => ['sample_key' => 'sample_value'],
                'expectedUrl' => '?grid%5Bsample_grid%5D=sample_key%3Dsample_value',
            ],
            'not empty url with grid params' => [
                'url' => 'sample/url',
                'gridName' => 'sample_grid',
                'gridParams' => ['sample_key' => 'sample_value'],
                'expectedUrl' => 'sample/url?grid%5Bsample_grid%5D=sample_key%3Dsample_value',
            ],
            'with host' => [
                'url' => 'example.com/sample/url',
                'gridName' => 'sample_grid',
                'gridParams' => ['sample_key' => 'sample_value'],
                'expectedUrl' => 'example.com/sample/url?grid%5Bsample_grid%5D=sample_key%3Dsample_value',
            ],
            'with empty scheme' => [
                'url' => '//example.com/sample/url',
                'gridName' => 'sample_grid',
                'gridParams' => ['sample_key' => 'sample_value'],
                'expectedUrl' => '//example.com/sample/url?grid%5Bsample_grid%5D=sample_key%3Dsample_value',
            ],
            'with http scheme' => [
                'url' => 'http://example.com/sample/url',
                'gridName' => 'sample_grid',
                'gridParams' => ['sample_key' => 'sample_value'],
                'expectedUrl' => 'http://example.com/sample/url?grid%5Bsample_grid%5D=sample_key%3Dsample_value',
            ],
            'with query' => [
                'url' => 'http://example.com/sample/url?sample=value',
                'gridName' => 'sample_grid',
                'gridParams' => ['sample_key' => 'sample_value'],
                'expectedUrl' => 'http://example.com/sample/url?sample=value&'
                    . 'grid%5Bsample_grid%5D=sample_key%3Dsample_value',
            ],
            'with fragment' => [
                'url' => 'http://example.com/sample/url?sample=value#sample-fragment',
                'gridName' => 'sample_grid',
                'gridParams' => ['sample_key' => 'sample_value'],
                'expectedUrl' => 'http://example.com/sample/url?sample=value&'
                    . 'grid%5Bsample_grid%5D=sample_key%3Dsample_value#sample-fragment',
            ],
            'with user' => [
                'url' => 'http://john@example.com/sample/url?sample=value#sample-fragment',
                'gridName' => 'sample_grid',
                'gridParams' => ['sample_key' => 'sample_value'],
                'expectedUrl' => 'http://john@example.com/sample/url?sample=value&'
                    . 'grid%5Bsample_grid%5D=sample_key%3Dsample_value#sample-fragment',
            ],
            'with user and password' => [
                'url' => 'http://john:doe@example.com/sample/url?sample=value#sample-fragment',
                'gridName' => 'sample_grid',
                'gridParams' => ['sample_key' => 'sample_value'],
                'expectedUrl' => 'http://john:doe@example.com/sample/url?sample=value&'
                    . 'grid%5Bsample_grid%5D=sample_key%3Dsample_value#sample-fragment',
            ],
        ];
    }
}
