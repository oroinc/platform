<?php

namespace Oro\Bundle\CacheBundle\Tests\Functional\Serializer\Normalizer;

use Oro\Bundle\CacheBundle\Serializer\Normalizer\GetSetMethodNormalizer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class GetSetMethodNormalizerTest extends WebTestCase
{
    private GetSetMethodNormalizer $normalizer;

    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->normalizer = self::getContainer()->get('oro.cache.serializer.normalizer');
        $this->serializer = new Serializer([$this->normalizer], [new JsonEncoder()]);
    }

    public function testNormalizer(): void
    {
        $entity = new User();
        $entity->setUsername('UserName');
        $entity->setEmail('username@mail.test');
        $entity->setTitle('User Title');
        $entity->setGoogleId('googleId');
        $entity->setPhone('+123456678');

        $result = json_decode($this->serializer->serialize($entity, 'json'), true);

        $this->assertEquals(
            [
                'userName' => 'UserName',
                'email' => 'username@mail.test',
                'title' => 'User Title',
                'googleId' => 'googleId',
                'phone' => '+123456678',
            ],
            [
                'userName' => $result['username'],
                'email' => $result['email'],
                'title' => $result['title'],
                'googleId' => $result['googleId'],
                'phone' => $result['phone'],
            ],
        );
    }

    public function testDenormalizer(): void
    {
        $data = [
            'userName' => 'UserName',
            'email' => 'username@mail.test',
            'title' => 'User Title',
            'googleId' => 'googleId',
            'phone' => '+123456678',
        ];

        $expectedEntity = new User();
        $expectedEntity->setUsername('UserName');
        $expectedEntity->setEmail('username@mail.test');
        $expectedEntity->setTitle('User Title');
        $expectedEntity->setGoogleId('googleId');
        $expectedEntity->setPhone('+123456678');

        $result = $this->serializer->deserialize(json_encode($data), User::class, 'json');

        $this->assertEquals(
            [
                $expectedEntity->getUsername(),
                $expectedEntity->getEmail(),
                $expectedEntity->getTitle(),
                $expectedEntity->getGoogleId(),
                $expectedEntity->getPhone()
            ],
            [
                $result->getUsername(),
                $result->getEmail(),
                $result->getTitle(),
                $result->getGoogleId(),
                $result->getPhone()
            ]
        );
    }
}
