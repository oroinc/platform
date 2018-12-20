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
        $factory = new DuplicatorFactory();
        $filter = new SetNullFilter();
        $matcher = new PropertyNameMatcher('firstField');
        /** @var FilterFactory|\PHPUnit\Framework\MockObject\MockObject $filterFactory */
        $filterFactory = $this->createMock('Oro\Component\Duplicator\Filter\FilterFactory');
        $filterFactory->expects($this->once())->method('create')->with('setNull', [])->willReturn($filter);

        /** @var MatcherFactory|\PHPUnit\Framework\MockObject\MockObject $matcherFactory */
        $matcherFactory = $this->createMock('Oro\Component\Duplicator\Matcher\MatcherFactory');
        $matcherFactory->expects($this->once())
            ->method('create')
            ->with('propertyName', ['firstField'])
            ->willReturn($matcher);

        $factory->setFilterFactory($filterFactory);
        $factory->setMatcherFactory($matcherFactory);

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
