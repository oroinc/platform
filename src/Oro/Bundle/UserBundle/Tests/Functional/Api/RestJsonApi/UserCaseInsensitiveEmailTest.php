<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class UserCaseInsensitiveEmailTest extends RestJsonApiTestCase
{
    /** @var GlobalScopeManager */
    private $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);

        $this->configManager = $this
            ->getClientInstance()
            ->getContainer()
            ->get('oro_config.global');
    }

    public function testCreateAndUpdateCaseSensitive()
    {
        if ($this->getRepository()->isCaseInsensitiveCollation()) {
            $this->markTestSkipped('Case insensitive email option can\'t be disabled.');
        }

        $this->configManager->set('oro_user.case_insensitive_email_addresses_enabled', false);
        $this->configManager->flush();

        $entityType = $this->getEntityType(User::class);

        $response = $this->post(['entity' => $entityType], $this->getData());
        $user = $this->assertRequestSuccess($response, $this->getData(), Response::HTTP_CREATED);

        $data = $this->getData();
        $data['data']['id'] = (string)$user->getId();
        $data['data']['attributes']['email'] = 'NewEmail@Test.Com';
        $data['data']['attributes']['firstName'] = 'John';

        $response = $this->patch(['entity' => $entityType, 'id' => $user->getId()], $data);
        $this->assertRequestSuccess($response, $data, Response::HTTP_OK);
    }

    public function testCreateAndUpdateCaseInsensitive()
    {
        $this->configManager->set('oro_user.case_insensitive_email_addresses_enabled', true);
        $this->configManager->flush();

        $entityType = $this->getEntityType(User::class);

        $response = $this->post(['entity' => $entityType], $this->getData(), [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains('unique user email constraint', $response->getContent());
        static::assertTrue(null === $this->getUser('testapiuser'));

        $data = $this->getData();
        $data['data']['attributes']['email'] = 'Email@Test.Com';

        $response = $this->post(['entity' => $entityType], $data);
        $user = $this->assertRequestSuccess($response, $data, Response::HTTP_CREATED);

        $data['data']['id'] = (string)$user->getId();
        $data['data']['attributes']['email'] = 'NewEmail@Test.Com';
        $data['data']['attributes']['firstName'] = 'John';

        $response = $this->patch(['entity' => $entityType, 'id' => $user->getId()], $data);
        $this->assertRequestSuccess($response, $data, Response::HTTP_OK);
    }


    /**
     * @param Response $response
     * @param array $data
     * @param int $expectedCode
     * @return User
     */
    private function assertRequestSuccess(Response $response, array $data, int $expectedCode): User
    {
        static::assertResponseStatusCodeEquals($response, $expectedCode);

        $data = $data['data']['attributes'];
        $user = $this->getUser($data['username']);

        static::assertNotNull($user);
        static::assertEquals($data['email'], $user->getEmail());
        static::assertEquals($data['firstName'], $user->getFirstName());
        static::assertEquals($data['lastName'], $user->getLastName());

        return $user;
    }

    /**
     * @return string
     */
    private function getAliceFolderName(): string
    {
        return 'user';
    }

    /**
     * @param string $username
     * @return User|null
     */
    private function getUser($username): ?User
    {
        return $this->getRepository()
            ->findOneBy(['username' => $username]);
    }

    /**
     * @return UserRepository
     */
    private function getRepository(): UserRepository
    {
        return $this->getEntityManager()
            ->getRepository(User::class);
    }

    /**
     * @return array
     */
    private function getData(): array
    {
        return [
            'data' => [
                'type' => $this->getEntityType(User::class),
                'attributes' => [
                    'username' => 'testapiuser',
                    'email' => 'System_User_2@Example.Com',
                    'firstName' => 'Bob',
                    'lastName' => 'Fedeson',
                ],
            ],
        ];
    }
}
