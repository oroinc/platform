<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Board;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Board\RestrictionManager;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\UIBundle\Provider\UserAgent;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class RestrictionManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserAgentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $userAgentProvider;

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowRegistry;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var RestrictionManager */
    private $manager;

    protected function setUp(): void
    {
        $this->userAgentProvider = $this->createMock(UserAgentProvider::class);
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);

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

        $userAgent = $this->createMock(UserAgent::class);
        $userAgent->expects($this->once())
            ->method('isDesktop')
            ->willReturn(true);
        $this->userAgentProvider->expects($this->once())
            ->method('getUserAgent')
            ->willReturn($userAgent);

        $this->entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->with('Test:Entity')
            ->willReturn('Test\Entity');
        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with('Test\Entity')
            ->willReturn(new ArrayCollection());

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

        $userAgent = $this->createMock(UserAgent::class);
        $userAgent->expects($this->once())
            ->method('isDesktop')
            ->willReturn(false);
        $this->userAgentProvider->expects($this->once())
            ->method('getUserAgent')
            ->willReturn($userAgent);

        $this->assertFalse($this->manager->boardViewEnabled($config));
    }
}
