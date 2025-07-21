<?php

namespace Oro\Component\Duplicator\Tests\Unit;

use DeepCopy\Filter\Doctrine\DoctrineCollectionFilter;
use DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter;
use DeepCopy\Filter\KeepFilter;
use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyMatcher;
use DeepCopy\Matcher\PropertyNameMatcher;
use DeepCopy\Matcher\PropertyTypeMatcher;
use Oro\Component\Duplicator\DuplicatorFactory;
use Oro\Component\Duplicator\Filter\FilterFactory;
use Oro\Component\Duplicator\Filter\ReplaceValueFilter;
use Oro\Component\Duplicator\Filter\ShallowCopyFilter;
use Oro\Component\Duplicator\Matcher\MatcherFactory;
use Oro\Component\Duplicator\ObjectType;
use PHPUnit\Framework\TestCase;

abstract class DuplicatorTestCase extends TestCase
{
    protected function createMatcherFactory(): MatcherFactory
    {
        $factory = new MatcherFactory();
        $factory->addObjectType(new ObjectType('property', PropertyMatcher::class));
        $factory->addObjectType(new ObjectType('propertyName', PropertyNameMatcher::class));
        $factory->addObjectType(new ObjectType('propertyType', PropertyTypeMatcher::class));

        return $factory;
    }

    protected function createFilterFactory(): FilterFactory
    {
        $factory = new FilterFactory();
        $factory->addObjectType(new ObjectType('setNull', SetNullFilter::class));
        $factory->addObjectType(new ObjectType('keep', KeepFilter::class));
        $factory->addObjectType(new ObjectType('collection', DoctrineCollectionFilter::class));
        $factory->addObjectType(new ObjectType('emptyCollection', DoctrineEmptyCollectionFilter::class));
        $factory->addObjectType(new ObjectType('replaceValue', ReplaceValueFilter::class));
        $factory->addObjectType(new ObjectType('shallowCopy', ShallowCopyFilter::class));

        return $factory;
    }

    protected function createDuplicatorFactory(): DuplicatorFactory
    {
        return new DuplicatorFactory($this->createMatcherFactory(), $this->createFilterFactory());
    }
}
