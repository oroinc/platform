<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadTranslations extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const TRANSLATION1 = 'translation.trans1';
    const TRANSLATION2 = 'translation.trans2';
    const TRANSLATION3 = 'translation.trans3';
    const TRANSLATION4 = 'translation.trans4';
    const TRANSLATION5 = 'translation.trans5';

    const TRANSLATION_KEY_1 = 'translation.trans1';
    const TRANSLATION_KEY_2 = 'translation.trans2';
    const TRANSLATION_KEY_3 = 'translation.trans3';
    const TRANSLATION_KEY_4 = 'translation.trans4';
    const TRANSLATION_KEY_5 = 'translation.trans5';

    const TRANSLATION_KEY_DOMAIN = 'test_domain';

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
        $this->createTranslationKey($manager, self::TRANSLATION_KEY_1, self::TRANSLATION_KEY_DOMAIN);
        $this->createTranslationKey($manager, self::TRANSLATION_KEY_2, self::TRANSLATION_KEY_DOMAIN);
        $this->createTranslationKey($manager, self::TRANSLATION_KEY_3, self::TRANSLATION_KEY_DOMAIN);
        $this->createTranslationKey($manager, self::TRANSLATION_KEY_4, self::TRANSLATION_KEY_DOMAIN);
        $this->createTranslationKey($manager, self::TRANSLATION_KEY_5, self::TRANSLATION_KEY_DOMAIN);

        $this->createTranslation($manager, self::TRANSLATION1, LoadLanguages::LANGUAGE1);
        $this->createTranslation($manager, self::TRANSLATION2, LoadLanguages::LANGUAGE1);
        $this->createTranslation($manager, self::TRANSLATION3, LoadLanguages::LANGUAGE2);
        $this->createTranslation($manager, self::TRANSLATION4, LoadLanguages::LANGUAGE2, Translation::SCOPE_INSTALLED);
        $this->createTranslation($manager, self::TRANSLATION5, LoadLanguages::LANGUAGE2, Translation::SCOPE_UI);

        $this->container->get('oro_translation.provider.translation_domain')->clearCache();

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $key
     * @param string $locale
     * @param int $scope
     *
     * @return Translation
     */
    protected function createTranslation(ObjectManager $manager, $key, $locale, $scope = Translation::SCOPE_SYSTEM)
    {
        $translation = new Translation();
        $translationKey = $this->getReference(sprintf('tk-%s-%s', $key, self::TRANSLATION_KEY_DOMAIN));
        $language = $this->getReference($locale);
        $translation
            ->setTranslationKey($translationKey)
            ->setValue($key)
            ->setLanguage($language)
            ->setScope($scope);

        $manager->persist($translation);
        $this->addReference($key, $translation);

        return $translation;
    }

    /**
     * @param ObjectManager $manager
     * @param string $key
     * @param string $domain
     *
     * @return TranslationKey
     */
    protected function createTranslationKey(ObjectManager $manager, $key, $domain)
    {
        $translationKey = (new TranslationKey())->setDomain($domain)->setKey($key);
        $manager->persist($translationKey);
        $this->addReference(sprintf('tk-%s-%s', $key, $domain), $translationKey);

        return $translationKey;
    }
}
