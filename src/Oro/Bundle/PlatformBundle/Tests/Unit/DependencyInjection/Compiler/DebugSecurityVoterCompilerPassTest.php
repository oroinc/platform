<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\DebugSecurityVoterCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Security\Core\Authorization\TraceableAccessDecisionManager;

class DebugSecurityVoterCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var DebugSecurityVoterCompilerPass */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerBuilder::class);
        $this->compilerPass = new DebugSecurityVoterCompilerPass();
    }

    public function testRemoveVoteListener(): void
    {
        $this->container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap([
                ['debug.security.access.decision_manager', true],
                ['debug.security.voter.vote_listener', true],
            ]);

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with('debug.security.access.decision_manager')
            ->willReturn(new Definition(TraceableAccessDecisionManager::class));

        $this->container->expects($this->once())
            ->method('removeDefinition')
            ->with('debug.security.voter.vote_listener');

        $this->compilerPass->process($this->container);
    }

    public function testNotRemoveListenerWhenDecisionNotTraceable(): void
    {
        $this->container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap([
                ['debug.security.access.decision_manager', true],
                ['debug.security.voter.vote_listener', true],
            ]);

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with('debug.security.access.decision_manager')
            ->willReturn(new Definition(\stdClass::class));

        $this->container->expects($this->never())
            ->method('removeDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testNotRemoveListenerWhenNotHasVoter(): void
    {
        $this->container->expects($this->atLeastOnce())
            ->method('hasDefinition')
            ->willReturnMap([
                ['debug.security.access.decision_manager', true],
                ['debug.security.voter.vote_listener', false],
            ]);

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->container->expects($this->never())
            ->method('removeDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testNotRemoveListenerWhenNotHasDecision(): void
    {
        $this->container->expects($this->atLeastOnce())
            ->method('hasDefinition')
            ->willReturnMap([
                ['debug.security.access.decision_manager', false],
                ['debug.security.voter.vote_listener', true],
            ]);

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->container->expects($this->never())
            ->method('removeDefinition');

        $this->compilerPass->process($this->container);
    }
}
