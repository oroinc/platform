<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

class LoadTranslations extends AbstractFixture implements DependentFixtureInterface
{
    const TRANSLATION1 = 'translation.trans1';
    const TRANSLATION2 = 'translation.trans2';
    const TRANSLATION3 = 'translation.trans3';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadLanguages::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTranslation($manager, self::TRANSLATION1, LoadLanguages::LANGUAGE1);
        $this->createTranslation($manager, self::TRANSLATION2, LoadLanguages::LANGUAGE1);
        $this->createTranslation($manager, self::TRANSLATION3, LoadLanguages::LANGUAGE2);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $key
     * @param string $locale
     * @return Translation
     */
    protected function createTranslation(ObjectManager $manager, $key, $locale)
    {
        $translation = new Translation();
        $translationKey = (new TranslationKey())->setDomain('test_domain')->setKey($key);
        $manager->persist($translationKey);
        $translation
            ->setTranslationKey($translationKey)
            ->setValue($key)
            ->setLanguage($this->getReference($locale));
        $manager->persist($translation);
        $this->addReference($key, $translation);

        return $translation;
    }
}
