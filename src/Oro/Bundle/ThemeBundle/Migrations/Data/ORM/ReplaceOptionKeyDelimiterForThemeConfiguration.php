<?php

namespace Oro\Bundle\ThemeBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Replace the dash '-' delimiter to '__' for the configuration option keys
 */
class ReplaceOptionKeyDelimiterForThemeConfiguration extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // Only for upgrade application
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $newConfiguration = [];
        foreach ($this->getAllThemeConfigurations($manager) as $themeConfiguration) {
            $configuration = $themeConfiguration->getConfiguration();
            foreach ($configuration as $key => $value) {
                $newConfiguration[str_replace('-', LayoutThemeConfiguration::OPTION_KEY_DELIMITER, $key)] = $value;
            }

            $themeConfiguration->setConfiguration($newConfiguration);
            $newConfiguration = [];
        }

        $manager->flush();
    }

    private function getAllThemeConfigurations(ObjectManager $manager): array
    {
        return $manager->getRepository(ThemeConfiguration::class)->findAll();
    }
}
