<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\DataGridBundle\Converter\UrlConverter;
use Symfony\Component\Routing\RouterInterface;

class UrlConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var UrlConverter */
    private $datagridUrlConverter;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->datagridUrlConverter = new UrlConverter($this->router);
    }

    /**
     * @dataProvider convertGridUrlToPageUrlInvalidUrlProvider
     *
     * @param $url
     * @param $gridName
     * @param $expectedResult
     */
    public function testConvertGridUrlToPageUrlInvalidUrl($url, $gridName, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            $this->datagridUrlConverter->convertGridUrlToPageUrl($gridName, $url)
        );
    }

    /**
     * @return array
     */
    public function convertGridUrlToPageUrlInvalidUrlProvider()
    {
        $gridName = 'gridName';
        $gridRequestParams = \http_build_query(
            [
                $gridName => [
                    '_appearance' => ['_type' => 'grid',],
                ],
            ]
        );

        return [
            'no parameters in url' => [
                'url' => '/admin/datagrid/quotes-grid',
                'gridName' => $gridName,
                'expectedResult' => '/admin/datagrid/quotes-grid',
            ],
            'no datagrid parameter in url' => [
                'url' => '/admin/datagrid/quotes-grid?test=none',
                'gridName' => $gridName,
                'expectedResult' => '/admin/datagrid/quotes-grid?test=none',
            ],
            'no originalUrl parameter in url' => [
                'url' => '/admin/datagrid/quotes-grid?' . $gridRequestParams,
                'gridName' => $gridName,
                'expectedResult' => '/admin/datagrid/quotes-grid?' . $gridRequestParams,
            ],
        ];
    }

    public function testConvertGridUrlToPageUrlTryConvertPageUrl()
    {
        $gridName = 'quotes-grid';

        $pageParams = [
            $gridName =>
                [
                    'originalRoute' => 'oro_sale_quote_index',
                    '_pager' =>
                        [
                            '_page' => '1',
                            '_per_page' => '10',
                        ],
                    '_parameters' =>
                        [
                            'view' => '__all__',
                        ],
                    '_appearance' =>
                        [
                            '_type' => 'grid',
                        ],
                ],
            'appearanceType' => 'grid',
        ];

        $requestUri = '/admin/sale/quote?'  . \http_build_query($pageParams);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('oro_sale_quote_index')
            ->willReturn('/admin/sale/quote');
        ;

        $this->assertEquals(
            $requestUri,
            $this->datagridUrlConverter->convertGridUrlToPageUrl($gridName, $requestUri)
        );
    }

    public function testConvertGridUrlToPageUrl()
    {
        $gridName = 'quotes-grid';
        $pageParams = [
            $gridName =>
                [
                    'originalRoute' => 'oro_sale_quote_index',
                    '_pager' =>
                        [
                            '_page' => '1',
                            '_per_page' => '10',
                        ],
                    '_parameters' =>
                        [
                            'view' => '__all__',
                        ],
                    '_appearance' =>
                        [
                            '_type' => 'grid',
                        ],
                ],
            'appearanceType' => 'grid',
        ];

        $requestUri = '/admin/datagrid/quotes-grid?' . \http_build_query($pageParams);
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('oro_sale_quote_index')
            ->willReturn('/admin/sale/quote');

        $this->assertEquals(
            '/admin/sale/quote?' . \http_build_query([$gridName => $pageParams[$gridName]]),
            $this->datagridUrlConverter->convertGridUrlToPageUrl($gridName, $requestUri)
        );
    }
}
