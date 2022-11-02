<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Updates default localization formatting code
 */
class UpdateDefaultLocalizationFormattingCode extends AbstractFixture
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Localization $defaultLocalization */
        $defaultLocalization = $manager->getRepository(Localization::class)->find(1);
        $enUSLocalization = $manager->getRepository(Localization::class)
            ->findOneBy(['name' => 'English (United States)']);

        if ($defaultLocalization && !$enUSLocalization &&
            $defaultLocalization->getName() === 'English' && $defaultLocalization->getFormattingCode() === 'en') {
            $defaultLocalization->setFormattingCode('en_US');
            $defaultLocalization->setName('English (United States)');
            $defaultLocalization->setDefaultTitle('English (United States)');
            $manager->flush();
        }
    }
}
