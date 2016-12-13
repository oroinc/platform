<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Helper;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;

class TranslationsDatagridRouteHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var DatagridRouteHelper|\PHPUnit_Framework_MockObject_MockObject */
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
            ['f' => ['filterName' => ['value' => '10']]],
            RouterInterface::ABSOLUTE_PATH
        )->willReturn('generatedValue');

        $this->assertEquals('generatedValue', $this->helper->generate(['filterName' => 10]));
    }
}
