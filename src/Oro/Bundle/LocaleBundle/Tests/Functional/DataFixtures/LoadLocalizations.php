<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LoadLocalizations extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createLocalization($manager, 'en', 'en', 'en');
        $this->createLocalization($manager, 'en_US', 'en_US', 'en_US');
        $this->createLocalization($manager, 'en_CA', 'en_CA', 'en_CA');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param string $language
     * @param string $formatting
     *
     * @return Localization
     */
    protected function createLocalization(ObjectManager $manager, $name, $language, $formatting)
    {
        $entity = new Localization();
        $entity->setName($name)
            ->setDefaultTitle($name)
            ->setLanguageCode($language)
            ->setFormattingCode($formatting);

        $manager->persist($entity);

        $this->addReference($name, $entity);

        return $entity;
    }
}
