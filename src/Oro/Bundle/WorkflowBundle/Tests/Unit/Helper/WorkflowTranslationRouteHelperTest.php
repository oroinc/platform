<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationRouteHelper;

class WorkflowTranslationRouteHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DatagridRouteHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagridRouteHelperMock;

    protected function setUp()
    {
        $this->datagridRouteHelperMock = $this->getMockBuilder(DatagridRouteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->datagridRouteHelperMock);
    }

    public function testGenerate()
    {
        $workflowName = 'SAMPLE_WF_NAME';
        $data = ['f' => [WorkflowTranslationRouteHelper::TRANSLATION_FILTER_NAME => ['value' => $workflowName]]];

        $this->datagridRouteHelperMock->expects($this->once())->method('generate')->with(
            WorkflowTranslationRouteHelper::TRANSLATION_GRID_ROUTE_NAME,
            WorkflowTranslationRouteHelper::TRANSLATION_GRID_NAME,
            $data,
            Router::ABSOLUTE_PATH
        )->willReturn(uniqid('', true));

        $helper = new WorkflowTranslationRouteHelper($this->datagridRouteHelperMock);

        $result = $helper->generate($workflowName);

        $this->assertInternalType('string', $result);
    }
}
