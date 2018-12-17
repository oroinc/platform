<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\OriginalUrlProvider;
use Oro\Bundle\DataGridBundle\Converter\UrlConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class OriginalUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var OriginalUrlProvider */
    private $urlProvider;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var UrlConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridUrlConverter;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->datagridUrlConverter = $this->createMock(UrlConverter::class);
        $this->urlProvider = new OriginalUrlProvider(
            $this->requestStack,
            $this->router,
            $this->datagridUrlConverter
        );
    }

    public function testGetOriginalUrl()
    {
        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($this->getRequest('example.com'));

        $this->assertEquals('example.com', $this->urlProvider->getOriginalUrl());
    }

    public function testGetOriginalUrlWhenDatagridIsSet()
    {
        $datagridName = 'quotes-grid';
        $pageParams = [
            'quotes-grid' =>
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
        $responseUri = '/admin/sale/quote?' . \http_build_query([$datagridName => $pageParams[$datagridName]]);

        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($this->getRequest($requestUri));

        $this->datagridUrlConverter
            ->expects($this->once())
            ->method('convertGridUrlToPageUrl')
            ->with($datagridName, $requestUri)
            ->willReturn($responseUri);

        $buttonContext = $this->getSearchButtonContext($datagridName);

        $this->assertEquals(
            $responseUri,
            $this->urlProvider->getOriginalUrl($buttonContext)
        );
    }

    /**
     *
     * @param string $requestUri
     * @return Request
     */
    private function getRequest(string $requestUri)
    {
        return new Request([], [], [], [], [], ['REQUEST_URI' => $requestUri]);
    }

    /**
     * @param string|null $datagridName
     * @return ButtonSearchContext
     */
    private function getSearchButtonContext($datagridName)
    {
        $btnContext = new ButtonSearchContext();
        $btnContext->setDatagrid($datagridName);

        return $btnContext;
    }
}
