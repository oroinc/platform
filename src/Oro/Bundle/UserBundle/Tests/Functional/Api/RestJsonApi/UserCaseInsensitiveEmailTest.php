<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;

/**
 * @dbIsolationPerTest
 */
class UserCaseInsensitiveEmailTest extends RestJsonApiTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadUserData::class]);
    }

    private function setCaseInsensitiveEmailAddresses(bool $value)
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_user.case_insensitive_email_addresses_enabled', $value);
        $configManager->flush();
    }

    private function getUserRepository(): UserRepository
    {
        return $this->getEntityManager()->getRepository(User::class);
    }

    private function findUser(string $userName): ?User
    {
        return $this->getUserRepository()->findOneBy(['username' => $userName]);
    }

    private function getData(): array
    {
        return [
            'data' => [
                'type'       => 'users',
                'attributes' => [
                    'username'  => 'testapiuser',
                    'email'     => 'System_User_2@Example.Com',
                    'firstName' => 'Bob',
                    'lastName'  => 'Fedeson'
                ]
            ]
        ];
    }

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

    public function testFindUserByEmail()
    {
        $this->setCaseInsensitiveEmailAddresses(true);
        $response = $this->cget(['entity' => 'users'], [
            'filter[email]' => 'Admin@example.com'
        ]);
        $content = self::jsonToArray($response->getContent());
        $this->assertNotEmpty($content);
        $this->assertArrayHasKey('data', $content);
        $this->assertIsArray($content['data']);
        $this->assertCount(1, $content['data']);
        $this->assertEquals('admin@example.com', $content['data'][0]['attributes']['email']);

        $this->setCaseInsensitiveEmailAddresses(false);
        $response = $this->cget(['entity' => 'users'], [
            'filter[email]' => 'Admin@example.com'
        ]);
        $this->assertResponseCount(0, $response);
    }
}
