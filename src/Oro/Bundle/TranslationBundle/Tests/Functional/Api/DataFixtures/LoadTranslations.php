<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

class LoadTranslations extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->addReference(
            'en',
            $manager->getRepository(Language::class)
                ->createQueryBuilder('e')
                ->where('e.code = :code')
                ->setParameter('code', 'en')
                ->getQuery()
                ->getSingleResult()
        );
        $this->createLanguage($manager, 'en_US');
        $this->createLanguage($manager, 'en_CA');
        $this->createLanguage($manager, 'fr_FR');

        $this->createTranslationKey($manager, 'test.trans', 'messages');
        $this->createTranslationKey($manager, 'test.trans1', 'test_domain');
        $this->createTranslationKey($manager, 'test.trans2', 'test_domain');
        $this->createTranslationKey($manager, 'another.trans1', 'test_domain');

        $this->createTranslation($manager, 'test.trans1', 'test_domain', 'en');
        $this->createTranslation($manager, 'test.trans1', 'test_domain', 'en_CA');
        $this->createTranslation($manager, 'test.trans1', 'test_domain', 'fr_FR');
        $this->createTranslation($manager, 'test.trans2', 'test_domain', 'en_CA');
        $this->createTranslation($manager, 'another.trans1', 'test_domain', 'fr_FR');

        $manager->flush();
    }

    private function createLanguage(ObjectManager $manager, string $code): Language
    {
        $language = new Language();
        $language
            ->setCode($code)
            ->setEnabled(true)
            ->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));

        $manager->persist($language);
        $this->addReference($code, $language);

        return $language;
    }

    private function createTranslationKey(ObjectManager $manager, string $key, string $domain): TranslationKey
    {
        $translationKey = (new TranslationKey())->setDomain($domain)->setKey($key);
        $manager->persist($translationKey);
        $this->addReference($this->getTranslationKeyReferenceName($key, $domain), $translationKey);

        return $translationKey;
    }

    private function createTranslation(
        ObjectManager $manager,
        string $key,
        string $domain,
        string $locale
    ): Translation {
        $translation = new Translation();
        $translation
            ->setTranslationKey($this->getReference($this->getTranslationKeyReferenceName($key, $domain)))
            ->setValue(sprintf('%s (%s)', str_replace('.', ' ', $key), $locale))
            ->setLanguage($this->getReference($locale))
            ->setScope(Translation::SCOPE_UI);

        $manager->persist($translation);
        $this->addReference(sprintf('%s-%s-%s', str_replace('.', '_', $key), $domain, $locale), $translation);

        return $translation;
    }

    private function getTranslationKeyReferenceName(string $key, string $domain): string
    {
        return sprintf('tk-%s-%s', str_replace('.', '_', $key), $domain);
    }
}
