<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

/**
 * Loads demo languages
 */
class LoadLanguageDemoData extends AbstractFixture
{
    use UserUtilityTrait;

    const LANG_DE_DE = 'language.de_DE';
    const LANG_FR_FR = 'language.fr_FR';
    const LANG_EN_US = 'language.en_US';
    const LANG_EN_CA = 'language.en_CA';
    const LANG_EN_GB = 'language.en_GB';
    const LANG_EN_AU = 'language.en_AU';
    const LANG_ES_AR = 'language.es_AR';
    const LANG_FR_CA = 'language.fr_CA';

    /** @var array */
    protected static $languages = [
        self::LANG_DE_DE => 'de_DE',
        self::LANG_FR_FR => 'fr_FR',
        self::LANG_EN_US => 'en_US',
        self::LANG_EN_CA => 'en_CA',
        self::LANG_EN_GB => 'en_GB',
        self::LANG_EN_AU => 'en_AU',
        self::LANG_ES_AR => 'es_AR',
        self::LANG_FR_CA => 'fr_CA'
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $organization = $user->getOrganization();
        $languageRepository = $manager->getRepository(Language::class);

        $qb = $languageRepository
            ->createQueryBuilder('l')
            ->select('l');
        $qb->where($qb->expr()->in('l.code', ':codes'))
            ->setParameter('codes', array_values(self::$languages));
        /** @var Language[] $languages */
        $languages = $qb->getQuery()->getResult();

        $existingLanguages = [];
        foreach ($languages as $language) {
            $existingLanguages[$language->getCode()] = $language;
        }

        $flushRequired = false;
        foreach (self::$languages as $reference => $code) {
            if (isset($existingLanguages[$code])) {
                $language = $existingLanguages[$code];
            } else {
                $language = (new Language())
                    ->setCode($code)
                    ->setEnabled(true)
                    ->setOrganization($organization);

                $manager->persist($language);
                $flushRequired = true;
            }

            $this->addReference($reference, $language);
        }

        if ($flushRequired) {
            $manager->flush();
        }
    }
}
