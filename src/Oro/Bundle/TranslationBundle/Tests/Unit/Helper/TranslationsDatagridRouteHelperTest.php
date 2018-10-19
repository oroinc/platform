<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Helper;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Symfony\Component\Routing\RouterInterface;

class TranslationsDatagridRouteHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridRouteHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $datagridRouteHelper;

    /** @var TranslationsDatagridRouteHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->datagridRouteHelper = $this->getMockBuilder(DatagridRouteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new TranslationsDatagridRouteHelper($this->datagridRouteHelper);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->datagridRouteHelper, $this->helper);
    }

    public function testGenerate()
    {
        $this->datagridRouteHelper->expects($this->once())->method('generate')->with(
            TranslationsDatagridRouteHelper::TRANSLATION_GRID_ROUTE_NAME,
            TranslationsDatagridRouteHelper::TRANSLATION_GRID_NAME,
            ['f' => ['filterName' => ['value' => '10', 'type' => '20']]],
            RouterInterface::ABSOLUTE_PATH
        )->willReturn('generatedValue');

        $this->assertEquals('generatedValue', $this->helper->generate(['filterName' => 10], 1, ['filterName' => 20]));
    }
}
