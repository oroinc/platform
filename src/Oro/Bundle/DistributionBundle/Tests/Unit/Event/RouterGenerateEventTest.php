<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Event;

use Oro\Bundle\DistributionBundle\Event\RouterGenerateEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class RouterGenerateEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $routeName = 'test_route';
        $parameters = ['id' => 1, 'slug' => 'test'];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL;

        $event = new RouterGenerateEvent($routeName, $parameters, $referenceType);

        self::assertEquals($routeName, $event->getRouteName());
        self::assertEquals($parameters, $event->getParameters());
        self::assertEquals($referenceType, $event->getReferenceType());
    }

    public function testSetRouteName(): void
    {
        $event = new RouterGenerateEvent('original_route', [], UrlGeneratorInterface::ABSOLUTE_PATH);

        $newRouteName = 'modified_route';
        $event->setRouteName($newRouteName);

        self::assertEquals($newRouteName, $event->getRouteName());
    }

    public function testSetParameters(): void
    {
        $event = new RouterGenerateEvent('test_route', ['id' => 1], UrlGeneratorInterface::ABSOLUTE_PATH);

        $newParameters = ['id' => 2, 'name' => 'test'];
        $event->setParameters($newParameters);

        self::assertEquals($newParameters, $event->getParameters());
    }

    public function testGetParameter(): void
    {
        $parameters = ['id' => 123, 'slug' => 'test-slug'];
        $event = new RouterGenerateEvent('test_route', $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);

        self::assertEquals(123, $event->getParameter('id'));
        self::assertEquals('test-slug', $event->getParameter('slug'));
    }

    public function testGetParameterReturnsNullForNonExistent(): void
    {
        $event = new RouterGenerateEvent('test_route', ['id' => 1], UrlGeneratorInterface::ABSOLUTE_PATH);

        self::assertNull($event->getParameter('non_existent'));
    }

    public function testSetParameter(): void
    {
        $event = new RouterGenerateEvent('test_route', ['id' => 1], UrlGeneratorInterface::ABSOLUTE_PATH);

        $event->setParameter('name', 'John');

        self::assertEquals('John', $event->getParameter('name'));
        self::assertEquals(['id' => 1, 'name' => 'John'], $event->getParameters());
    }

    public function testSetParameterOverwritesExisting(): void
    {
        $event = new RouterGenerateEvent('test_route', ['id' => 1], UrlGeneratorInterface::ABSOLUTE_PATH);

        $event->setParameter('id', 999);

        self::assertEquals(999, $event->getParameter('id'));
    }

    public function testHasParameter(): void
    {
        $event = new RouterGenerateEvent(
            'test_route',
            ['id' => 1, 'name' => null],
            UrlGeneratorInterface::ABSOLUTE_PATH
        );

        self::assertTrue($event->hasParameter('id'));
        self::assertTrue($event->hasParameter('name'));
        self::assertFalse($event->hasParameter('non_existent'));
    }

    public function testRemoveParameter(): void
    {
        $event = new RouterGenerateEvent(
            'test_route',
            ['id' => 1, 'slug' => 'test'],
            UrlGeneratorInterface::ABSOLUTE_PATH
        );

        $event->removeParameter('slug');

        self::assertFalse($event->hasParameter('slug'));
        self::assertEquals(['id' => 1], $event->getParameters());
    }

    public function testRemoveNonExistentParameter(): void
    {
        $event = new RouterGenerateEvent('test_route', ['id' => 1], UrlGeneratorInterface::ABSOLUTE_PATH);

        $event->removeParameter('non_existent');

        self::assertEquals(['id' => 1], $event->getParameters());
    }

    public function testSetReferenceType(): void
    {
        $event = new RouterGenerateEvent('test_route', [], UrlGeneratorInterface::ABSOLUTE_PATH);

        $event->setReferenceType(UrlGeneratorInterface::ABSOLUTE_URL);

        self::assertEquals(UrlGeneratorInterface::ABSOLUTE_URL, $event->getReferenceType());
    }

    public function testWithEmptyParameters(): void
    {
        $event = new RouterGenerateEvent('test_route', [], UrlGeneratorInterface::RELATIVE_PATH);

        self::assertEquals([], $event->getParameters());
        self::assertNull($event->getParameter('any_key'));
        self::assertFalse($event->hasParameter('any_key'));
    }

    public function testMultipleParameterOperations(): void
    {
        $event = new RouterGenerateEvent('test_route', ['a' => 1], UrlGeneratorInterface::ABSOLUTE_PATH);

        $event->setParameter('b', 2);
        $event->setParameter('c', 3);
        $event->removeParameter('a');

        self::assertEquals(['b' => 2, 'c' => 3], $event->getParameters());
    }

    public function testWithDifferentReferenceTypes(): void
    {
        $event1 = new RouterGenerateEvent('route', [], UrlGeneratorInterface::ABSOLUTE_PATH);
        self::assertEquals(UrlGeneratorInterface::ABSOLUTE_PATH, $event1->getReferenceType());

        $event2 = new RouterGenerateEvent('route', [], UrlGeneratorInterface::ABSOLUTE_URL);
        self::assertEquals(UrlGeneratorInterface::ABSOLUTE_URL, $event2->getReferenceType());

        $event3 = new RouterGenerateEvent('route', [], UrlGeneratorInterface::RELATIVE_PATH);
        self::assertEquals(UrlGeneratorInterface::RELATIVE_PATH, $event3->getReferenceType());

        $event4 = new RouterGenerateEvent('route', [], UrlGeneratorInterface::NETWORK_PATH);
        self::assertEquals(UrlGeneratorInterface::NETWORK_PATH, $event4->getReferenceType());
    }
}
