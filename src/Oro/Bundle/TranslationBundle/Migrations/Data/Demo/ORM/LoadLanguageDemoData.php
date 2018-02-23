<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

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

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $organization = $user->getOrganization();
        $languageRepository = $manager->getRepository(Language::class);

        foreach (self::$languages as $reference => $code) {
            $language = $languageRepository->findOneBy(['code' => $code]);
            if (!$language) {
                $language = new Language();
                $language->setCode($code)
                    ->setEnabled(true)
                    ->setOwner($user)
                    ->setOrganization($organization);

                $manager->persist($language);
            }
            $this->addReference($reference, $language);
        }

        $manager->flush();
    }
}
