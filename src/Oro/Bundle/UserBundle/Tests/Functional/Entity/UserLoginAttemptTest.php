<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\UserLoginAttempt;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class UserLoginAttemptTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * @dataProvider userAgentDataProvider
     */
    public function testUserAgentLengthInDb(string $userAgent): void
    {
        $entityManager = $this->getEntityManager();

        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $uuid = UUIDGenerator::v4();

        $userLoginAttempt = new UserLoginAttempt();
        $userLoginAttempt->setId($uuid);
        $userLoginAttempt->setUser($user);
        $userLoginAttempt->setSource(1);
        $userLoginAttempt->setAttemptAt(new \DateTime('now'));
        $userLoginAttempt->setIp('127.0.0.1');
        $userLoginAttempt->setUserAgent($userAgent);
        $userLoginAttempt->setSuccess(1);
        $userLoginAttempt->setUsername('userName');
        $userLoginAttempt->setContext([]);

        $entityManager->persist($userLoginAttempt);
        $entityManager->flush();

        $savedCustomerLoginAttempt = $entityManager->getRepository(UserLoginAttempt::class)
            ->findOneBy(['id' => $uuid]);

        self::assertNotNull($savedCustomerLoginAttempt);
    }

    public function userAgentDataProvider(): array
    {
        return [
            [
                "Mozilla\\/5.0 (iPhone; CPU iPhone OS 17_3_1 like Mac OS X) AppleWebKit\\/605.1.15 (KHTML, like Gecko) "
                . "Mobile\\/21D61 [FBAN\\/FBIOS;FBAV\\/452.0.0.39.110;FBBV\\/569146793;FBDV\\/iPhone13,3;FBMD\\/iPhone;"
                . "FBSN\\/iOS;FBSV\\/17.3.1;FBSS\\/3;FBID\\/phone;FBLC\\/nl_NL;FBOP\\/5;FBRV\\/571609390]"
            ],
            [
                "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0"
            ],
            [
                "Mozilla/5.0"
            ]
        ];
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(UserLoginAttempt::class);
    }
}
