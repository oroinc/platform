<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Specification\Stub;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Specification\Locator\SpecificationLocator;
use Behat\Testwork\Specification\SpecificationArrayIterator;
use Behat\Testwork\Suite\Suite;

class SpecificationLocatorStub implements SpecificationLocator
{
    /**
     * @var int
     */
    protected $featureCount;

    /**
     * @param int $featureCount
     */
    public function __construct($featureCount)
    {
        $this->featureCount = $featureCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocatorExamples()
    {
        return 'Return iterators with count of features specified in constructor. For unit tests only';
    }

    /**
     * {@inheritdoc}
     */
    public function locateSpecifications(Suite $suite, $locator = null)
    {
        return new SpecificationArrayIterator(
            $suite,
            array_fill(0, $this->featureCount, new FeatureNode(null, null, [], null, [], '', '', 'fake.feature', 0))
        );
    }
}
