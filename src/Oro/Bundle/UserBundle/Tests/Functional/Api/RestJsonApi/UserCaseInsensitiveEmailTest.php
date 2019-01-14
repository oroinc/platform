<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;

/**
 * @dbIsolationPerTest
 */
class UserCaseInsensitiveEmailTest extends RestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * @param bool $value
     */
    private function setCaseInsensitiveEmailAddresses(bool $value)
    {
        /** @var ConfigManager $configManager */
        $configManager = self::getContainer()->get('oro_config.global');
        $configManager->set('oro_user.case_insensitive_email_addresses_enabled', $value);
        $configManager->flush();
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository(): UserRepository
    {
        return $this->getEntityManager()->getRepository(User::class);
    }

    /**
     * @param string $userName
     *
     * @return User|null
     */
    private function findUser(string $userName): ?User
    {
        return $this->getUserRepository()->findOneBy(['username' => $userName]);
    }

    /**
     * @return array
     */
    private function getData(): array
    {
        return [
            'data' => [
                'type'       => $this->getEntityType(User::class),
                'attributes' => [
                    'username'  => 'testapiuser',
                    'email'     => 'System_User_2@Example.Com',
                    'firstName' => 'Bob',
                    'lastName'  => 'Fedeson'
                ]
            ]
        ];
    }

    /**
     * @param array $data
     *
     * @return User
     */
    private function assertRequestSuccess(array $data): User
    {
        $attributes = $data['data']['attributes'];

        $user = $this->findUser($attributes['username']);
        self::assertNotNull($user);
        self::assertEquals($attributes['email'], $user->getEmail());
        self::assertEquals($attributes['firstName'], $user->getFirstName());
        self::assertEquals($attributes['lastName'], $user->getLastName());

        return $user;
    }

    public function testCreateAndUpdateCaseSensitive()
    {
        if ($this->getUserRepository()->isCaseInsensitiveCollation()) {
            $this->markTestSkipped('Case insensitive email option cannot be disabled.');
        }

        $this->setCaseInsensitiveEmailAddresses(false);

        $data = $this->getData();
        $this->post(['entity' => 'users'], $data);
        $user = $this->assertRequestSuccess($data);

        $data['data']['id'] = (string)$user->getId();
        $data['data']['attributes']['email'] = 'NewEmail@Test.Com';
        $data['data']['attributes']['firstName'] = 'John';
        $this->patch(['entity' => 'users', 'id' => $user->getId()], $data);
        $this->assertRequestSuccess($data);
    }

    public function testCreateAndUpdateCaseInsensitive()
    {
        $this->setCaseInsensitiveEmailAddresses(true);

        $data = $this->getData();
        $response = $this->post(['entity' => 'users'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'unique user email constraint',
                'detail' => 'This email is already registered by another user. Please provide unique email address.',
                'source' => ['pointer' => '/data/attributes/email']
            ],
            $response
        );
        self::assertTrue(null === $this->findUser($data['data']['attributes']['username']));

        $data['data']['attributes']['email'] = 'Email@Test.Com';
        $this->post(['entity' => 'users'], $data);
        $user = $this->assertRequestSuccess($data);

        $data['data']['id'] = (string)$user->getId();
        $data['data']['attributes']['email'] = 'NewEmail@Test.Com';
        $data['data']['attributes']['firstName'] = 'John';

        $this->patch(['entity' => 'users', 'id' => $user->getId()], $data);
        $this->assertRequestSuccess($data);
    }
}
