<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\EmailTemplateHydrator;

use Oro\Bundle\EmailBundle\EmailTemplateHydrator\EmailTemplateFromArrayHydrator;
use Oro\Bundle\EmailBundle\Event\EmailTemplateFromArrayHydrateBeforeEvent;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EmailTemplateFromArrayHydratorTest extends TestCase
{
    public function testHydrateFromArrayAllWritable(): void
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);

        $data = [
            'name' => 'test_template',
            'subject' => 'Test Subject',
            'type' => 'html',
            'content' => 'Test Content',
        ];

        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $event = new EmailTemplateFromArrayHydrateBeforeEvent($emailTemplate, $data);

        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EmailTemplateFromArrayHydrateBeforeEvent::class))
            ->willReturn($event);

        $propertyAccessor
            ->expects(self::exactly(count($data)))
            ->method('isWritable')
            ->with($emailTemplate, self::callback(fn ($key) => array_key_exists($key, $data)))
            ->willReturn(true);

        $propertyAccessor
            ->expects(self::exactly(count($data)))
            ->method('setValue')
            ->withConsecutive(
                [$emailTemplate, 'name', 'test_template'],
                [$emailTemplate, 'subject', 'Test Subject'],
                [$emailTemplate, 'type', 'html'],
                [$emailTemplate, 'content', 'Test Content'],
            );

        $hydrator = new EmailTemplateFromArrayHydrator($propertyAccessor, $eventDispatcher);
        $hydrator->hydrateFromArray($emailTemplate, $data);
    }

    public function testHydrateFromArraySomeNotWritable(): void
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);

        $data = [
            'name' => 'test_template',
            'subject' => 'Test Subject',
            'type' => 'html',
            'content' => 'Test Content',
        ];

        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $event = new EmailTemplateFromArrayHydrateBeforeEvent($emailTemplate, $data);

        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EmailTemplateFromArrayHydrateBeforeEvent::class))
            ->willReturn($event);

        // Only 'name' and 'type' are writable
        $propertyAccessor
            ->expects(self::exactly(count($data)))
            ->method('isWritable')
            ->withConsecutive(
                [$emailTemplate, 'name'],
                [$emailTemplate, 'subject'],
                [$emailTemplate, 'type'],
                [$emailTemplate, 'content'],
            )
            ->willReturnOnConsecutiveCalls(true, false, true, false);

        // Only setValue for writable properties
        $propertyAccessor
            ->expects(self::exactly(2))
            ->method('setValue')
            ->withConsecutive(
                [$emailTemplate, 'name', 'test_template'],
                [$emailTemplate, 'type', 'html'],
            );

        $hydrator = new EmailTemplateFromArrayHydrator($propertyAccessor, $eventDispatcher);
        $hydrator->hydrateFromArray($emailTemplate, $data);
    }

    public function testHydrateFromArrayWithChangedEventData(): void
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);

        $originalData = [
            'name' => 'test_template',
            'subject' => 'Test Subject',
            'type' => 'html',
            'content' => 'Test Content',
        ];

        $changedData = [
            'name' => 'changed_template',
            'subject' => 'Changed Subject',
            'type' => 'txt',
            'content' => 'Changed Content',
        ];

        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // Simulate event changes the data
        $event = new EmailTemplateFromArrayHydrateBeforeEvent($emailTemplate, $originalData);
        $event->setData($changedData);

        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EmailTemplateFromArrayHydrateBeforeEvent::class))
            ->willReturn($event);

        $propertyAccessor
            ->expects(self::exactly(count($changedData)))
            ->method('isWritable')
            ->with($emailTemplate, self::callback(fn ($key) => array_key_exists($key, $changedData)))
            ->willReturn(true);

        $propertyAccessor
            ->expects(self::exactly(count($changedData)))
            ->method('setValue')
            ->withConsecutive(
                [$emailTemplate, 'name', 'changed_template'],
                [$emailTemplate, 'subject', 'Changed Subject'],
                [$emailTemplate, 'type', 'txt'],
                [$emailTemplate, 'content', 'Changed Content'],
            );

        $hydrator = new EmailTemplateFromArrayHydrator($propertyAccessor, $eventDispatcher);
        $hydrator->hydrateFromArray($emailTemplate, $originalData);
    }
}
