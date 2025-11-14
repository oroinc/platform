<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Oro\Bundle\TestFrameworkBundle\Behat\BehatFeature;
use Symfony\Component\HttpKernel\KernelInterface;

class TagsBasedFeatureToggleContext implements Context
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    /**
     * @BeforeScenario
     */
    public function activateTagFeatures(BeforeScenarioScope $scope): void
    {
        $tags = $scope->getFeature()->getTags();
        $this->getBehatFeature()->setActiveTags($tags);
    }

    /**
     * @AfterScenario
     */
    public function deactivateTagFeatures(): void
    {
        $this->getBehatFeature()->clearActiveTags();
    }

    private function getBehatFeature(): BehatFeature
    {
        return $this->kernel->getContainer()->get('oro_test.behat.feature');
    }
}
