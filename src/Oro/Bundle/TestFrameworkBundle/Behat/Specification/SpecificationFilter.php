<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Specification;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\GenericSuite;

class SpecificationFilter
{
    /**
     * @var SpecificationFinder
     */
    private $specificationFinder;

    public function __construct(SpecificationFinder $specificationFinder)
    {
        $this->specificationFinder = $specificationFinder;
    }

    /**
     * @param array $paths
     * @return array Paths to features excluded non executable
     */
    public function filter(array $paths)
    {
        $suite = new GenericSuite('GenericSuite', ['paths' => $paths]);
        $iterators = $this->specificationFinder->findSuitesSpecifications([$suite]);
        $features = [];

        foreach ($iterators as $iterator) {
            /** @var FeatureNode $featureNode */
            foreach ($iterator as $featureNode) {
                $features[$featureNode->getFile()] = null;
            }
        }

        return array_keys($features);
    }
}
