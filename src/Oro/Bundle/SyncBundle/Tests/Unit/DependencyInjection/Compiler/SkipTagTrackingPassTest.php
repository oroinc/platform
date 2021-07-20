<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\SkipTagTrackingPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SkipTagTrackingPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var SkipTagTrackingPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new SkipTagTrackingPass();
    }

    public function testProcessWithoutService()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $listenerDef = $container->register('oro_sync.event_listener.doctrine_tag');

        $this->compiler->process($container);

        $skippedEntityClasses = [];
        foreach ($listenerDef->getMethodCalls() as [$methodName, $methodArguments]) {
            if ('markSkipped' === $methodName) {
                $skippedEntityClasses[] = $methodArguments[0];
            }
        }
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
                'Oro\Bundle\BatchBundle\Entity\JobExecution',
                'Oro\Bundle\BatchBundle\Entity\StepExecution',
                'Oro\Bundle\MessageQueueBundle\Entity\Job',
            ],
            $skippedEntityClasses
        );
    }
}
