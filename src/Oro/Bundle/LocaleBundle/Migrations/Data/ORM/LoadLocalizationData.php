<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LoadLocalizationData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $localeCode = 'en';
        $title = Intl::getLanguageBundle()->getLanguageName($localeCode, null, $localeCode);

        $localization = $manager->getRepository('OroLocaleBundle:Localization')->findOneBy(['name' => $title]);

        if (!$localization) {
            $localization = new Localization();
            $localization->setName($title)
                ->setDefaultTitle($title)
                ->setLanguageCode($localeCode)
                ->setFormattingCode($localeCode);

            $manager->persist($localization);
            $manager->flush();
        }

        $this->addReference('default_localization', $localization);
    }
}
