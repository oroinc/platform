<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Component\Testing\ResponseExtension;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

use FOS\RestBundle\Util\Codes;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class EntityDataControllerTest extends WebTestCase
{
    use ResponseExtension;

    public function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader(), true);
        $this->loadFixtures([
            'Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadUserData',
            'Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadRoleData',
            'Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData'
        ]);
    }

    public function testShouldNotAllowUpdateEntityDataIfBlocked()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch('/api/rest/latest/entity_data/Oro_Bundle_UserBundle_Entity_User/'.$user->getId(), '{"id": 1}');

        $this->assertLastResponseStatus(Codes::HTTP_FORBIDDEN);
        $this->assertLastResponseContentTypeJson();
    }

    public function testShouldReturnNotFoundIfSuchEntityNotExist()
    {
        $this->sendPatch('/api/rest/latest/entity_data/Oro_Bundle_UserBundle_Entity_NotExist/10', '{"id": 1}');

        $this->assertLastResponseStatus(Codes::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertLastResponseContentTypeJson();
    }

    public function testShouldCorrectlyUpdateStringEntityField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            '/api/rest/latest/entity_data/Oro_Bundle_UserBundle_Entity_User/'.$user->getId(),
            '{"firstName": "Test1"}'
        );

        $this->assertLastResponseStatus(Codes::HTTP_NO_CONTENT);

        $this->refreshEntity($user);
        $this->assertEquals('Test1', $user->getFirstName());
    }

    public function testShouldCorrectlyUpdateIntEntityField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            '/api/rest/latest/entity_data/Oro_Bundle_UserBundle_Entity_User/'.$user->getId(),
            '{"loginCount": 10}'
        );

        $this->assertLastResponseStatus(Codes::HTTP_NO_CONTENT);

        $this->refreshEntity($user);
        $this->assertEquals(10, $user->getLoginCount());
    }

    public function testShouldCorrectlyUpdateBooleanEntityField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            '/api/rest/latest/entity_data/Oro_Bundle_UserBundle_Entity_User/'.$user->getId(),
            '{"enabled": false}'
        );

        $this->assertLastResponseStatus(Codes::HTTP_NO_CONTENT);

        $this->refreshEntity($user);
        $this->assertFalse($user->isEnabled());
    }

    public function testShouldCorrectlyUpdateDateEntityField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            '/api/rest/latest/entity_data/Oro_Bundle_UserBundle_Entity_User/'.$user->getId(),
            '{"birthday": "2000-05-05T00:00:00+0000"}'
        );

        $this->assertLastResponseStatus(Codes::HTTP_NO_CONTENT);

        $this->refreshEntity($user);
        $this->assertEquals(new \DateTime('2000-05-05T00:00:00+0000'), $user->getBirthday());
    }

    public function testShouldCorrectlyUpdateDateTimeEntityField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            '/api/rest/latest/entity_data/Oro_Bundle_UserBundle_Entity_User/'.$user->getId(),
            '{"lastLogin":"2000-05-05T01:05:05+0000"}'
        );

        $this->assertLastResponseStatus(Codes::HTTP_NO_CONTENT);

        $this->refreshEntity($user);
        $this->assertEquals(new \DateTime('2000-05-05T01:05:05+0000'), $user->getLastLogin());
    }

    public function testShouldNotAllowChangeEntityFieldValueIfNotValid()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            '/api/rest/latest/entity_data/Oro_Bundle_UserBundle_Entity_User/'.$user->getId(),
            '{"email": "test"}'
        );

        $this->assertLastResponseStatus(Codes::HTTP_BAD_REQUEST);
        $this->assertLastResponseContentTypeJson();

        $content = $this->getLastResponseJsonContent();
        $this->assertEquals('Validation Failed', $content['message']);
    }

    public function testShouldNotAllowChangeEntityFieldValueIfNotValidBecauseNewValueIsEmpty()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            '/api/rest/latest/entity_data/Oro_Bundle_UserBundle_Entity_User/'.$user->getId(),
            '{"username": ""}'
        );

        $this->assertLastResponseStatus(Codes::HTTP_BAD_REQUEST);
        $this->assertLastResponseContentTypeJson();

        $content = $this->getLastResponseJsonContent();
        $this->assertEquals('Validation Failed', $content['message']);
    }

    /**
     * @param string $className
     * @param string $field
     * @param mixed $reference
     * @param int $responseCode
     *
     * @dataProvider setAssociationDataProvider
     */
    public function testAssociationFieldPatch($className, $field, $reference, $responseCode)
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $classNameSafe = $this->getContainer()
            ->get('oro_entity.entity_class_name_helper')->getUrlSafeClassName($className);
        $id = $user->getId();
        $fieldName = $field;

        if (is_array($reference)) {
            $ids = [];
            foreach ($reference as $items) {
                $ids[] = $this->getReference($items)->getId();
            }

            $content = sprintf('{"%s":[%s]}', $fieldName, implode(',', $ids));
        } else {
            $reference = $this->getReference($reference);
            $content = sprintf('{"%s":"%s"}', $fieldName, $reference->getId());
        }

        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => $classNameSafe,
                'id' => $id
            ]),
            [],
            [],
            [],
            $content
        );

        $this->assertEquals($responseCode, $this->client->getResponse()->getStatusCode());

        if ($responseCode === Codes::HTTP_NO_CONTENT) {
            $this->getContainer()->get('doctrine')->getManager()->clear();
            $repository = $this->getContainer()->get('doctrine')->getRepository($className);
            $object = $repository->find($id);
            $accessor = PropertyAccess::createPropertyAccessor();

            if (is_array($reference)) {
                $this->assertEquals(count($reference), count($accessor->getValue($object, $field)));
            } else {
                $this->assertEquals($reference->getId(), $accessor->getValue($object, $field)->getId());
            }
        }
        if ($responseCode === Codes::HTTP_BAD_REQUEST) {
            $response = $this->getJsonResponseContent(
                $this->client->getResponse(),
                $this->client->getResponse()->getStatusCode()
            );
            $this->assertEquals('Validation Failed', $response['message']);
        }
    }

    /**
     * @param string $url
     * @param string $content
     */
    protected function sendPatch($url, $content)
    {
        $this->client->request('PATCH', $url, [], [], [], $content);
    }

    /**
     * @param object $entity
     */
    protected function refreshEntity($entity)
    {
        /** @var RegistryInterface $registry */
        $registry = $this->getContainer()->get('doctrine');

        $registry->getManager()->clear();
        $registry->getManager()->find(get_class($entity), $entity->getId());
    }

    /**
     * @return array
     */
    public function setAssociationDataProvider()
    {
        return [
            'entity one to one' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'currentStatus',
                'status1',
                Codes::HTTP_NO_CONTENT
            ],
            'entity one to many' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'groups',
                'status1',
                Codes::HTTP_BAD_REQUEST
            ],
            'entity many to many' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'roles',
                ['ROLE_TEST_1', 'ROLE_TEST_2'],
                Codes::HTTP_NO_CONTENT
            ],
            'entity many to one' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'owner',
                'TestBusinessUnit',
                Codes::HTTP_NO_CONTENT
            ]
        ];
    }
}
