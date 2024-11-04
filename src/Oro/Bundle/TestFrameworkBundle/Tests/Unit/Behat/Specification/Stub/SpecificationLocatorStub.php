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

    #[\Override]
    public function getLocatorExamples()
    {
        return 'Return iterators with count of features specified in constructor. For unit tests only';
    }

    #[\Override]
    public function locateSpecifications(Suite $suite, $locator = null)
    {
        $features = [];

        for ($i = 0; $i < $this->featureCount; $i++) {
            $features[] = new FeatureNode(null, null, [], null, [], '', '', '/' . uniqid('', true).'.feature', 0);
        }

        return new SpecificationArrayIterator($suite, $features);
    }
}
