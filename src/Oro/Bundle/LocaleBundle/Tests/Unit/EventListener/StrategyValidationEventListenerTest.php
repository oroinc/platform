<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\EventListener\StrategyValidationEventListener;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StrategyValidationEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var StrategyValidationEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new StrategyValidationEventListener(PropertyAccess::createPropertyAccessor());
    }

    public function testViolationsEmpty()
    {
        $violations = new ConstraintViolationList();
        $event = new StrategyValidationEvent($violations);
        $this->listener->buildErrors($event);

        $this->assertEquals([], $event->getErrors());
    }

    public function testInvalidValueWithNotExpectedClass()
    {
        $violations = new ConstraintViolationList(
            [
                new ConstraintViolation(
                    'test',
                    'test',
                    [],
                    new \stdClass,
                    'prop',
                    'fail'
                ),
            ]
        );
        $event = new StrategyValidationEvent($violations);
        $event->addError('existing');
        $this->listener->buildErrors($event);

        $this->assertEquals(['existing'], $event->getErrors());
    }

    public function testInvalidValuePropertyPath()
    {
        $violations = new ConstraintViolationList(
            [
                $this->getViolation(null),
            ]
        );
        $event = new StrategyValidationEvent($violations);
        $event->addError('existing');
        $this->listener->buildErrors($event);

        $this->assertEquals(['existing'], $event->getErrors());
    }

    public function testInvalidValueWithoutRoot()
    {
        $violations = new ConstraintViolationList(
            [
                $this->getViolation('prop', null),
            ]
        );
        $event = new StrategyValidationEvent($violations);
        $event->addError('existing');
        $this->listener->buildErrors($event);

        $this->assertEquals(['existing'], $event->getErrors());
    }

    public function testInvalidValueCollectionPathEmpty()
    {
        $violations = new ConstraintViolationList(
            [
                $this->getViolation('.test', new \stdClass()),
            ]
        );
        $event = new StrategyValidationEvent($violations);
        $event->addError('existing');
        $this->listener->buildErrors($event);

        $this->assertEquals(['existing'], $event->getErrors());
    }

    public function testInvalidValueCollectionWrongPath()
    {
        $violations = new ConstraintViolationList(
            [
                $this->getViolation('root.test', new \stdClass()),
            ]
        );
        $event = new StrategyValidationEvent($violations);
        $event->addError('existing');
        $this->listener->buildErrors($event);

        $this->assertEquals(['existing'], $event->getErrors());
    }

    public function testInvalidValueLocalizedFallbackValueToOneRoot()
    {
        $fallback = new LocalizedFallbackValue();
        $class = new \stdClass();
        $class->root = $fallback;

        $violations = new ConstraintViolationList([$this->getViolation('root', $class, $fallback)]);
        $event = new StrategyValidationEvent($violations);
        $event->addError('root: test');
        $this->listener->buildErrors($event);

        $this->assertEquals(['root: test'], $event->getErrors());
    }

    public function testInvalidValueLocalizedFallbackValueToOneProp()
    {
        $fallback = new LocalizedFallbackValue();
        $class = new \stdClass();
        $prop = new \stdClass();
        $prop->prop = $fallback;
        $class->root = $prop;

        $violations = new ConstraintViolationList([$this->getViolation('root.prop', $class, $fallback)]);
        $event = new StrategyValidationEvent($violations);
        $event->addError('root.prop: test');
        $this->listener->buildErrors($event);

        $this->assertEquals(['root.prop: test'], $event->getErrors());
    }

    public function testInvalidValueLocalizedFallbackValueToManyRoot()
    {
        $fallback = new LocalizedFallbackValue();
        $class = new \stdClass();
        $class->root = [$fallback];

        $violations = new ConstraintViolationList([$this->getViolation('root[0]', $class, $fallback)]);
        $event = new StrategyValidationEvent($violations);
        $event->addError('root[0]: test');
        $this->listener->buildErrors($event);

        $this->assertEquals(['root[default]: test'], $event->getErrors());
    }

    public function testInvalidValueLocalizedFallbackValueToManyProp()
    {
        $fallback = new LocalizedFallbackValue();
        $class = new \stdClass();
        $prop = new \stdClass();
        $prop->prop = $fallback;
        $class->root = [$prop];

        $violations = new ConstraintViolationList([$this->getViolation('root[0].prop', $class, $fallback)]);
        $event = new StrategyValidationEvent($violations);
        $event->addError('root[0].prop: test');
        $this->listener->buildErrors($event);

        $this->assertEquals(['root[0].prop: test'], $event->getErrors());
    }

    public function testInvalidValueLocalizedFallbackValueToMany()
    {
        $fallback = new LocalizedFallbackValue();
        $class = new \stdClass();
        $prop = new \stdClass();
        $prop->prop = [$fallback];
        $class->root = [$prop];

        $violations = new ConstraintViolationList([$this->getViolation('root[0].prop[0]', $class, $fallback)]);
        $event = new StrategyValidationEvent($violations);
        $event->addError('root[0].prop[0]: test');
        $this->listener->buildErrors($event);

        $this->assertEquals(['root[0].prop[default]: test'], $event->getErrors());
    }

    public function testInvalidValueLocalizedFallbackValueDoublePathIgnored()
    {
        $fallback = new LocalizedFallbackValue();
        $class = new \stdClass();
        $prop2 = new \stdClass();
        $prop2->prop = $fallback;
        $prop = new \stdClass();
        $prop->prop = [$prop2];
        $class->root = [$prop];

        $violations = new ConstraintViolationList([$this->getViolation('root[0].prop[0].prop', $class, $fallback)]);
        $event = new StrategyValidationEvent($violations);
        $event->addError('root[0].prop[0].prop: test');
        $this->listener->buildErrors($event);

        $this->assertEquals(['root[0].prop[0].prop: test'], $event->getErrors());
    }

    private function getViolation(?string $propertyPath = null, $root = null, $invalid = null): ConstraintViolation
    {
        return new ConstraintViolation(
            'test',
            'test',
            [],
            $root,
            $propertyPath,
            $invalid
        );
    }
}
