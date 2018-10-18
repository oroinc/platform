<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Board;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Board\RestrictionManager;

class RestrictionManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $userAgentProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $workflowRegistry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityClassResolver;

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

        $this->entityClassResolver = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new RestrictionManager(
            $this->workflowRegistry,
            $this->userAgentProvider,
            $this->entityClassResolver
        );
    }

    public function testBoardViewEnabledDesktopAndNoWorkflow()
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type'  => 'orm',
                'query' => [
                    'from' => [
                        ['table' => 'Test:Entity', 'alias' => 'rootAlias']
                    ]
                ]
            ]
        ]);

        $userAgent = $this->getMockBuilder('Oro\Bundle\UIBundle\Provider\UserAgent')
            ->disableOriginalConstructor()
            ->getMock();
        $userAgent->expects($this->once())
            ->method('isDesktop')
            ->will($this->returnValue(true));
        $this->userAgentProvider->expects($this->once())
            ->method('getUserAgent')
            ->will($this->returnValue($userAgent));

        $this->entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->with('Test:Entity')
            ->will($this->returnValue('Test\Entity'));
        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with('Test\Entity')
            ->will($this->returnValue(new ArrayCollection()));

        $this->assertTrue($this->manager->boardViewEnabled($config));
    }

    public function testBoardViewEnabledMobile()
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type'  => 'orm',
                'query' => [
                    'from' => [
                        ['table' => 'Test:Entity', 'alias' => 'rootAlias']
                    ]
                ]
            ]
        ]);

        $userAgent = $this->getMockBuilder('Oro\Bundle\UIBundle\Provider\UserAgent')
            ->disableOriginalConstructor()
            ->getMock();
        $userAgent->expects($this->once())
            ->method('isDesktop')
            ->will($this->returnValue(false));
        $this->userAgentProvider->expects($this->once())
            ->method('getUserAgent')
            ->will($this->returnValue($userAgent));

        $this->assertFalse($this->manager->boardViewEnabled($config));
    }
}
