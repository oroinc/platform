<?php

namespace Oro\Bundle\ThemeBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;

/**
 * Convert type field value to lower case.
 */
class ConvertTypeFieldValueToLowerCaseMigration extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $themeConfigurations = $manager->getRepository(ThemeConfiguration::class)->findAll();
        foreach ($themeConfigurations as $themeConfiguration) {
            if (empty($themeConfiguration->getType())) {
                continue;
            }

            $themeConfiguration->setType(\strtolower($themeConfiguration->getType()));
        }

        $manager->flush();
    }
}
