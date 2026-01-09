<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\RuntimeTester;

use Behat\Gherkin\Cache\CacheInterface;
use Behat\Gherkin\Loader\AbstractFileLoader;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink\WatchModeSessionHolder;

/**
 * Decorated base *.feature files loader with filter for watch mode.
 */
class FeatureFilesLoaderDecorator extends AbstractFileLoader
{
    protected ?CacheInterface $cache = null;
    protected array $preparedFeature = [];

    public function __construct(
        private readonly AbstractFileLoader $baseFileLoader,
        private readonly WatchModeSessionHolder $sessionHolder
    ) {
    }

    public function setFileCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function supports($resource): bool
    {
        return $this->baseFileLoader->supports($resource);
    }

    protected function doLoad($resource): array
    {
        if ($this->sessionHolder->isWatchFrom() && empty($this->preparedFeature)) {
            // override feature file cache if watch mode is use
            $this->baseFileLoader->setCache($this->cache);
            $this->preparedFeature = $this->filterByWatchFrom($this->baseFileLoader->load($resource));
        }
        if (!empty($this->preparedFeature)) {
            return $this->preparedFeature;
        }

        return $this->baseFileLoader->load($resource);
    }

    public function baseLoad($resource): array
    {
        return $this->baseFileLoader->load($resource);
    }

    protected function filterByWatchFrom(array $features): array
    {
        $result = [];
        $startStepLine = $this->sessionHolder->getWatchFrom();
        /** @var FeatureNode $feature */
        foreach ($features as $feature) {
            $scenarios = [];
            foreach ($feature->getScenarios() as $scenario) {
                $steps = $this->filterSteps($scenario, $startStepLine);
                if (empty($steps)) {
                    continue;
                }
                $scenarios[] = new ScenarioNode(
                    $scenario->getTitle(),
                    $scenario->getTags(),
                    $steps,
                    $scenario->getKeyword(),
                    $scenario->getLine()
                );
            }
            if (empty($scenarios)) {
                continue;
            }
            $result[] = new FeatureNode(
                $feature->getTitle(),
                $feature->getDescription(),
                $feature->getTags(),
                $feature->getBackground(),
                $scenarios,
                $feature->getKeyword(),
                $feature->getLanguage(),
                $feature->getFile(),
                $feature->getLine(),
            );
        }

        return $result;
    }

    protected function filterSteps(ScenarioInterface $scenario, int $startStepLine): array
    {
        $steps = [];
        foreach ($scenario->getSteps() as $step) {
            if ($step->getLine() < $startStepLine) {
                continue;
            }
            $steps[] = $step;
        }

        return $steps;
    }
}
