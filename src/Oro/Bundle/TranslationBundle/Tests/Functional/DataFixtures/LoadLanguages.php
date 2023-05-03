<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\User;

class LoadLanguages extends AbstractFixture implements DependentFixtureInterface
{
    const LANGUAGE1 = 'en_CA';
    const LANGUAGE2 = 'fr_FR';
    const LANGUAGE3 = 'en_US';

    public const LANGUAGES = [
        self::LANGUAGE1 => [
            'enabled' => false,
            'user' => 'admin'
        ],
        self::LANGUAGE2 => [
            'enabled' => true,
            'user' => 'admin'
        ],
        self::LANGUAGE3 => [
            'enabled' => true,
            'user' => LoadTranslationUsers::TRANSLATOR_USERNAME
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadTranslationUsers::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (static::LANGUAGES as $language => $definition) {
            $this->createLanguage($manager, $language, $definition);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @param array $options
     *
     * @return Language
     */
    protected function createLanguage(ObjectManager $manager, $code, array $options)
    {
        /* @var User $user */
        $user = $manager->getRepository(User::class)->findOneBy(['username' => $options['user']]);

        $language = new Language();
        $language
            ->setCode($code)
            ->setEnabled($options['enabled'])
            ->setOrganization($user->getOrganization());

        $manager->persist($language);
        $this->addReference($code, $language);

        return $language;
    }
}
