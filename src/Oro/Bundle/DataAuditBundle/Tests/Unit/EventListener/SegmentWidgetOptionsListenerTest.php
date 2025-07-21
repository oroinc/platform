<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataAuditBundle\EventListener\SegmentWidgetOptionsListener;
use Oro\Bundle\DataAuditBundle\SegmentWidget\ContextChecker;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SegmentWidgetOptionsListenerTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private SegmentWidgetOptionsListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->listener = new SegmentWidgetOptionsListener(
            $this->authorizationChecker,
            new ContextChecker()
        );
    }

    public function testListener(): void
    {
        $options = [
            'column'       => [],
            'extensions'   => [],
            'entityChoice' => 'choice',
        ];

        $expectedOptions = [
            'column'            => [],
            'extensions'        => [
                'orodataaudit/js/app/components/segment-component-extension',
            ],
            'entityChoice'      => 'choice',
        ];

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $event = new WidgetOptionsLoadEvent($options);
        $this->listener->onLoad($event);

        $this->assertEquals($expectedOptions, $event->getWidgetOptions());
    }

    public function testOnLoadWhenNotApplicable(): void
    {
        $options = [
            'column'                       => [],
            'extensions'                   => [],
            ContextChecker::DISABLED_PARAM => true
        ];

        $event = new WidgetOptionsLoadEvent($options);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->listener->onLoad($event);
        $this->assertEquals($options, $event->getWidgetOptions());
    }

    public function testOnLoadWhenNotGranted(): void
    {
        $options = [
            'column'       => [],
            'extensions'   => [],
        ];

        $event = new WidgetOptionsLoadEvent($options);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $this->listener->onLoad($event);
        $this->assertEquals($options, $event->getWidgetOptions());
    }
}
