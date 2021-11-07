<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Entity\PageState;
use Oro\Bundle\SearchBundle\Entity\IndexDatetime;
use Oro\Bundle\SearchBundle\Entity\IndexDecimal;
use Oro\Bundle\SearchBundle\Entity\IndexInteger;
use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\SearchBundle\Entity\Item;
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
                Audit::class,
                AuditField::class,
                PageState::class,
                NavigationHistoryItem::class,
                Item::class,
                IndexText::class,
                IndexInteger::class,
                IndexDecimal::class,
                IndexDatetime::class,
                JobExecution::class,
                StepExecution::class,
                Job::class,
            ],
            $skippedEntityClasses
        );
    }
}
