<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\SkipTagTrackingPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SkipTagTrackingPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var SkipTagTrackingPass */
    protected $skipTagTrackingPass;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->container = $this->createMock(ContainerBuilder::class);

        $this->skipTagTrackingPass = new SkipTagTrackingPass();
    }

    public function testProcessWithoutService()
    {
        $this->container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(SkipTagTrackingPass::SERVICE_ID))
            ->will($this->returnValue(false));

        $this->container
            ->expects($this->never())
            ->method('getDefinition');

        $this->skipTagTrackingPass->process($this->container);
    }
    
    public function testProcess()
    {
        $this->container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(SkipTagTrackingPass::SERVICE_ID))
            ->will($this->returnValue(true));

        $skippedEntityClasses = [];

        /** @var Definition|\PHPUnit\Framework\MockObject\MockObject $definition */
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->any())
            ->method('addMethodCall')
            ->with('markSkipped')
            ->willReturnCallback(function ($method, array $arguments) use (&$skippedEntityClasses) {
                $skippedEntityClasses[] = $arguments[0];
            });

        $this->container
            ->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(SkipTagTrackingPass::SERVICE_ID))
            ->will($this->returnValue($definition));

        $this->skipTagTrackingPass->process($this->container);

        self::assertEquals(
            [
                'Oro\Bundle\DataAuditBundle\Entity\Audit',
                'Oro\Bundle\DataAuditBundle\Entity\AuditField',
                'Oro\Bundle\NavigationBundle\Entity\PageState',
                'Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem',
                'Oro\Bundle\SearchBundle\Entity\Item',
                'Oro\Bundle\SearchBundle\Entity\IndexText',
                'Oro\Bundle\SearchBundle\Entity\IndexInteger',
                'Oro\Bundle\SearchBundle\Entity\IndexDecimal',
                'Oro\Bundle\SearchBundle\Entity\IndexDatetime',
                'Akeneo\Bundle\BatchBundle\Entity\JobExecution',
                'Akeneo\Bundle\BatchBundle\Entity\StepExecution',
                'Oro\Bundle\MessageQueueBundle\Entity\Job',
            ],
            $skippedEntityClasses
        );
    }
}
