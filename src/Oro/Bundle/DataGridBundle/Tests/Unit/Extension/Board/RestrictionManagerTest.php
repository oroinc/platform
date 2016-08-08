<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Board;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Board\RestrictionManager;

class RestrictionManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $userAgentProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $gridConfigurationHelper;

    /**
     * @var RestrictionManager
     */
    protected $manager;

    public function setUp()
    {
        $this->userAgentProvider = $this->getMockBuilder('Oro\Bundle\UIBundle\Provider\UserAgentProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowRegistry = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->gridConfigurationHelper = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new RestrictionManager(
            $this->workflowRegistry,
            $this->userAgentProvider,
            $this->gridConfigurationHelper
        );
    }

    public function testBoardViewEnabledDesktopAndNoWorkflow()
    {
        $config = DatagridConfiguration::create([]);

        $userAgent = $this->getMockBuilder('Oro\Bundle\UIBundle\Provider\UserAgent')->disableOriginalConstructor()
                    ->getMock();
        $userAgent->expects($this->once())->method('isDesktop')->will($this->returnValue(true));
        $this->userAgentProvider->expects($this->once())->method('getUserAgent')->will($this->returnValue($userAgent));

        $this->gridConfigurationHelper->expects($this->once())->method('getEntity')->will($this->returnValue('entity'));
        $this->workflowRegistry->expects($this->once())->method('getActiveWorkflowsByEntityClass')->with('entity')
            ->will($this->returnValue([]));

        $this->assertTrue($this->manager->boardViewEnabled($config));
    }

    public function testBoardViewEnabledMobile()
    {
        $config = DatagridConfiguration::create([]);
        $userAgent = $this->getMockBuilder('Oro\Bundle\UIBundle\Provider\UserAgent')->disableOriginalConstructor()
                    ->getMock();
        $userAgent->expects($this->once())->method('isDesktop')->will($this->returnValue(false));
        $this->userAgentProvider->expects($this->once())->method('getUserAgent')->will($this->returnValue($userAgent));

        $this->assertFalse($this->manager->boardViewEnabled($config));
    }
}
