<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Event\OperationAnnounceEvent;
use Oro\Bundle\ActionBundle\EventListener\OperationFeatureGuardListener;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OperationFeatureGuardListenerTest extends TestCase
{
    private FeatureChecker|MockObject $featureChecker;
    private OperationFeatureGuardListener $listener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->listener = new OperationFeatureGuardListener($this->featureChecker);
    }

    public function testCheckFeatureWhenEventIsNotAllowed(): void
    {
        $data = new ActionData();
        $operationDefinition = new OperationDefinition();
        $operationDefinition->setName('operation_name');
        $event = new OperationAnnounceEvent($data, $operationDefinition);
        $event->setAllowed(false);

        $this->featureChecker->expects($this->never())
            ->method('isResourceEnabled');

        $this->listener->checkFeature($event);
    }

    /**
     * @dataProvider isEnabledProvider
     */
    public function testCheckFeature(bool $isEnabled): void
    {
        $data = new ActionData();
        $operationDefinition = new OperationDefinition();
        $operationDefinition->setName('operation_name');
        $event = new OperationAnnounceEvent($data, $operationDefinition);

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with('operation_name', 'operations')
            ->willReturn($isEnabled);

        $this->listener->checkFeature($event);
        $this->assertSame($isEnabled, $event->isAllowed());
    }

    public static function isEnabledProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
