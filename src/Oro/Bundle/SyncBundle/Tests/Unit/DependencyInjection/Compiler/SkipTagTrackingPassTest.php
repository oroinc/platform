<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\SkipTagTrackingPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SkipTagTrackingPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var SkipTagTrackingPass */
    protected $skipTagTrackingPass;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->container = $this->getMock(ContainerBuilder::class);

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

        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $definition */
        $definition = $this->getMock(Definition::class);
        $definition->expects($this->exactly(12))
            ->method('addMethodCall')
            ->withConsecutive(
                ['markSkipped', ['Oro\Bundle\DataAuditBundle\Entity\Audit']],
                ['markSkipped', ['Oro\Bundle\DataAuditBundle\Entity\AuditData']],
                ['markSkipped', ['Oro\Bundle\NavigationBundle\Entity\PageState']],
                ['markSkipped', ['Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem']],
                ['markSkipped', ['Oro\Bundle\SearchBundle\Entity\Item']],
                ['markSkipped', ['Oro\Bundle\SearchBundle\Entity\IndexText']],
                ['markSkipped', ['Oro\Bundle\SearchBundle\Entity\IndexInteger']],
                ['markSkipped', ['Oro\Bundle\SearchBundle\Entity\IndexDecimal']],
                ['markSkipped', ['Oro\Bundle\SearchBundle\Entity\IndexDatetime']],
                ['markSkipped', ['Akeneo\Bundle\BatchBundle\Entity\JobExecution']],
                ['markSkipped', ['Akeneo\Bundle\BatchBundle\Entity\StepExecution']],
                ['markSkipped', ['JMS\JobQueueBundle\Entity\Job']]
            );

        $this->container
            ->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(SkipTagTrackingPass::SERVICE_ID))
            ->will($this->returnValue($definition));

        $this->skipTagTrackingPass->process($this->container);
    }
}
