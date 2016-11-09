<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowApplicationsHelper;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class WorkflowApplicationsHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ApplicationsHelperInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $applicationHelper;

    /** @var ApplicationsHelperInterface */
    protected $workflowApplicationHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->applicationHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowApplicationHelper = new WorkflowApplicationsHelper($this->applicationHelper);
    }

    public function testGetSetRouteParam()
    {
        $parameters = [
            ['dialogRoute', 'url1'],
            ['executionRoute', 'url2'],
            ['widgetRoute', 'url3']
        ];

        $this->assertPropertyAccessors($this->workflowApplicationHelper, $parameters);
    }

    public function testIsApplicationsValid()
    {
        $this->applicationHelper->expects($this->once())
            ->method('isApplicationsValid')
            ->with(['data'])
            ->willReturn('test');

        $this->assertSame('test', $this->workflowApplicationHelper->isApplicationsValid(['data']));
    }

    public function testGetCurrentApplication()
    {
        $this->applicationHelper->expects($this->once())
            ->method('getCurrentApplication')
            ->willReturn('test');

        $this->assertSame('test', $this->workflowApplicationHelper->getCurrentApplication());
    }
}
