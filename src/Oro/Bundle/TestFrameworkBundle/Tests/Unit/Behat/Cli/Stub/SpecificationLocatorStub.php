<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli\Stub;

use Behat\Testwork\Specification\Locator\SpecificationLocator;
use Behat\Testwork\Specification\NoSpecificationsIterator;
use Behat\Testwork\Specification\SpecificationArrayIterator;
use Behat\Testwork\Suite\Suite;
use Symfony\Component\Finder\Tests\Iterator\Iterator;

class SpecificationLocatorStub implements SpecificationLocator
{
    /**
     * @var array
     */
    protected $suiteNames;

    /**
     * @param array $suiteNames
     */
    public function __construct(array $suiteNames)
    {
        $this->suiteNames = $suiteNames;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocatorExamples()
    {
        return 'Return iterators for suites specified in constructor. For unit tests only';
    }

    /**
     * {@inheritdoc}
     */
    public function locateSpecifications(Suite $suite, $locator = null)
    {
        if (in_array($suite->getName(), $this->suiteNames)) {
            return new SpecificationArrayIterator($suite, ['/fake/path']);
        }

        return new NoSpecificationsIterator($suite);
    }
}
