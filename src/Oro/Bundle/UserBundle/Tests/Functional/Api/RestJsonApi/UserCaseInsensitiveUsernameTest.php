<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;
use Symfony\Component\HttpFoundation\Response;

class UserCaseInsensitiveUsernameTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadUserData::class]);
    }

    public function testUnsuccessfulCreateCaseInsensitive()
    {
        $data = $this->getData();
        $response = $this->post(['entity' => 'users'], $data, [], false);

        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertStringContainsString('This username is already registered by another user. '
            . 'Please provide unique username. Source: usernameLowercase.', $response->getContent());

        self::assertEmpty($this->findUser($data['data']['attributes']['email']));
    }

    public function testCreateAndUpdateCaseInsensitive()
    {
        $data = $this->getData();
        $data['data']['attributes']['username'] = 'Unique username';
        $this->post(['entity' => 'users'], $data);
        $user = $this->assertRequestSuccess($data);

        $data['data']['id'] = (string)$user->getId();
        $data['data']['attributes']['username'] = 'New unique username';
        $data['data']['attributes']['firstName'] = 'John';

        $this->patch(['entity' => 'users', 'id' => $user->getId()], $data);
        $this->assertRequestSuccess($data);
    }

    private function getData(): array
    {
        return [
            'data' => [
                'type'       => 'users',
                'attributes' => [
                    'username'  => 'System_user_2',
                    'email'     => 'System_User_3@Example.Com',
                    'firstName' => 'Bob',
                    'lastName'  => 'Fedeson'
                ]
            ]
        ];
    }

    private function assertRequestSuccess(array $data): User
    {
        $attributes = $data['data']['attributes'];

        $user = $this->findUser($attributes['email']);
        self::assertNotNull($user);
        self::assertEquals($attributes['username'], $user->getUsername());
        self::assertEquals($attributes['email'], $user->getEmail());
        self::assertEquals($attributes['firstName'], $user->getFirstName());
        self::assertEquals($attributes['lastName'], $user->getLastName());

        return $user;
    }

    private function getUserRepository(): UserRepository
    {
        return $this->getEntityManager()->getRepository(User::class);
    }

    private function findUser(string $email): ?User
    {
        return $this->getUserRepository()->findOneBy(['email' => $email]);
    }
}
