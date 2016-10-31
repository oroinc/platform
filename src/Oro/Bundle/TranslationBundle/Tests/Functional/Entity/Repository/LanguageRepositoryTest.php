<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Migrations\Data\Demo\ORM\LoadTranslationUsers;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;

/**
 * @dbIsolation
 */
class LanguageRepositoryTest extends WebTestCase
{
    /** @var EntityManager */
    protected $em;

    /** @var LanguageRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadLanguages::class]);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(Language::class);
        $this->repository = $this->em->getRepository(Language::class);

        /* @var $userRepository UserRepository */
        $userRepository = $this->getContainer()->get('doctrine')->getManagerForClass(User::class)
            ->getRepository(User::class);

        /* @var $user User */
        $user = $userRepository->findOneBy(['username' => LoadTranslationUsers::TRANSLATOR_USERNAME]);

        $token = new UsernamePasswordOrganizationToken($user, false, 'k', $user->getOrganization(), $user->getRoles());
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
    }

    public function testGetAvailableLanguageCodes()
    {
        $this->assertEmpty(array_diff(
            [
                LoadLanguages::LANGUAGE1,
                LoadLanguages::LANGUAGE2,
            ],
            $this->repository->getAvailableLanguageCodes()
        ));
    }

    public function testGetEnabledAvailableLanguageCodes()
    {
        $this->assertEmpty(array_diff(
            [
                LoadLanguages::LANGUAGE2,
            ],
            $this->repository->getAvailableLanguageCodes(true)
        ));
    }

    public function testGetAvailableLanguagesByCurrentUser()
    {
        /* @var $aclHelper AclHelper */
        $aclHelper = $this->getContainer()->get('oro_security.acl_helper');

        $this->assertEquals(
            [
                $this->getReference(LoadLanguages::LANGUAGE3)
            ],
            $this->repository->getAvailableLanguagesByCurrentUser($aclHelper)
        );
    }
}
