<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Twig;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Twig\WorkflowExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class WorkflowExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var WorkflowExtension */
    protected $extension;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_workflow.manager', $this->workflowManager)
            ->getContainer($this);

        $this->extension = new WorkflowExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowExtension::NAME, $this->extension->getName());
    }
}
