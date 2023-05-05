<?php

namespace Oro\Component\Duplicator\Tests\Unit;

use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyNameMatcher;
use Oro\Component\Duplicator\DuplicatorFactory;
use Oro\Component\Duplicator\Filter\FilterFactory;
use Oro\Component\Duplicator\Matcher\MatcherFactory;

class DuplicatorFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $filter = new SetNullFilter();
        $matcher = new PropertyNameMatcher('firstField');
        $filterFactory = $this->createMock(FilterFactory::class);
        $filterFactory->expects($this->once())
            ->method('create')
            ->with('setNull', [])
            ->willReturn($filter);

        $matcherFactory = $this->createMock(MatcherFactory::class);
        $matcherFactory->expects($this->once())
            ->method('create')
            ->with('propertyName', ['firstField'])
            ->willReturn($matcher);

        $factory = new DuplicatorFactory($matcherFactory, $filterFactory);
        $duplicator = $factory->create();

        $firstField = new \stdClass();
        $firstField->title = 'test';

        $object = new \stdClass();
        $object->firstField = $firstField;
        $object->title = 'test title';

        $copyObject = $duplicator->duplicate($object, [
            [['setNull'], ['propertyName', ['firstField']]],
        ]);

        $this->assertNotEquals($copyObject, $object);
        $this->assertSame($copyObject->title, $object->title);
        $this->assertNull($copyObject->firstField);
    }
}
