<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\WorkflowBundle\Fixer\OriginalUrlFixer;
use Symfony\Component\Routing\RouterInterface;

class OriginalUrlFixerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OriginalUrlFixer */
    private $fixHelper;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->fixHelper = new OriginalUrlFixer($this->router);
    }

    public function testFixGridAjaxUrlApplicable()
    {
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

        $originalUrl = '/admin/datagrid/quotes-grid?' . \http_build_query($pageParams);
        $datagridName = 'quotes-grid';

        $buttonContext = $this->getButtonContext($originalUrl, $datagridName);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('oro_sale_quote_index')
            ->willReturn('/admin/sale/quote');

        $this->fixHelper->fixGridAjaxUrl($buttonContext);

        $this->assertEquals(
            '/admin/sale/quote?' . \http_build_query([$datagridName => $pageParams[$datagridName]]),
            $buttonContext->getOriginalUrl()
        );
    }

    public function testFixGridAjaxUrlNotApplicableContextNotContainsAllParameters()
    {
        $originalUrl = '/admin/sale/quote';

        $buttonContext = $this->getButtonContext($originalUrl, null);

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->fixHelper->fixGridAjaxUrl($buttonContext);

        $this->assertEquals(
            '/admin/sale/quote',
            $buttonContext->getOriginalUrl()
        );
    }

    public function testFixGridAjaxUrlNotApplicableNoOriginalRouteParameter()
    {
        $originalUrl = '/admin/sale/quote';
        $datagridName = 'quotes-grid';

        $buttonContext = $this->getButtonContext($originalUrl, $datagridName);

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->fixHelper->fixGridAjaxUrl($buttonContext);

        $this->assertEquals(
            '/admin/sale/quote',
            $buttonContext->getOriginalUrl()
        );
    }

    public function testFixGridAjaxUrlNotApplicableBaseUrlEquals()
    {
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

        $originalUrl = '/admin/sale/quote?'  . \http_build_query($pageParams);
        $datagridName = 'quotes-grid';

        $buttonContext = $this->getButtonContext($originalUrl, $datagridName);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('oro_sale_quote_index')
            ->willReturn('/admin/sale/quote');

        $this->fixHelper->fixGridAjaxUrl($buttonContext);

        $this->assertEquals(
            $originalUrl,
            $buttonContext->getOriginalUrl()
        );
    }

    /**
     * @param string|null $originalUrl
     * @param string|null $datagridName
     * @return ButtonContext
     */
    private function getButtonContext($originalUrl, $datagridName)
    {
        $btnContext = new ButtonContext();
        $btnContext->setOriginalUrl($originalUrl);
        $btnContext->setDatagridName($datagridName);

        return $btnContext;
    }
}
