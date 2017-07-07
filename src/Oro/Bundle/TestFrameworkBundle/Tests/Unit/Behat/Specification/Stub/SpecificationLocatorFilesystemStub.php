<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Specification\Stub;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Specification\Locator\SpecificationLocator;
use Behat\Testwork\Specification\SpecificationArrayIterator;
use Behat\Testwork\Suite\Suite;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SpecificationLocatorFilesystemStub implements SpecificationLocator
{
    /**
     * @var array
     */
    protected $paths;

    /**
     * @param array $paths
     */
    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocatorExamples()
    {
        return 'Return iterator with features found in paths specified in constructor. For unit tests only';
    }

    /**
     * {@inheritdoc}
     */
    public function locateSpecifications(Suite $suite, $locator = null)
    {
        $finder = new Finder();
        $finder->name('*.feature');

        foreach ($this->paths as $path) {
            $finder->in($path);
        }

        $features = [];

        foreach ($finder as $item) {
            $features[] = new FeatureNode(null, null, [], null, [], '', '', $item->getRealPath(), 0);
        }

        return new SpecificationArrayIterator($suite, $features);
    }
}
