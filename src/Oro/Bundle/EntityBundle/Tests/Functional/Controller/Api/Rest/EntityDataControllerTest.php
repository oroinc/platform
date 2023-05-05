<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadRoleData;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ResponseExtension;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class EntityDataControllerTest extends WebTestCase
{
    use ResponseExtension;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([
            LoadUserData::class,
            LoadRoleData::class,
            LoadBusinessUnitData::class
        ]);
    }

    public function testShouldNotAllowUpdateEntityDataIfBlocked()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $this->sendPatch(
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => 'Oro_Bundle_UserBundle_Entity_User',
                'id' => $user->getId()
            ]),
            '{"id": 1}'
        );

        $this->assertLastResponseStatus(Response::HTTP_FORBIDDEN);
        $this->assertLastResponseContentTypeJson();
    }

    public function testShouldReturnNotFoundIfSuchEntityNotExist()
    {
        $this->sendPatch(
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => 'Oro_Bundle_UserBundle_Entity_NotExist',
                'id' => 10
            ]),
            '{"id": 1}'
        );

        $this->assertLastResponseStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertLastResponseContentTypeJson();
    }

    public function testShouldCorrectlyUpdateStringEntityField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => 'Oro_Bundle_UserBundle_Entity_User',
                'id' => $user->getId()
            ]),
            '{"firstName": "Test1"}'
        );

        $this->assertLastResponseStatus(Response::HTTP_OK);

        $this->refreshEntity($user);
        $this->assertEquals('Test1', $user->getFirstName());
    }

    public function testShouldCorrectlyUpdateIntEntityField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => 'Oro_Bundle_UserBundle_Entity_User',
                'id' => $user->getId()
            ]),
            '{"loginCount": 10}'
        );

        $this->assertLastResponseStatus(Response::HTTP_OK);

        $this->refreshEntity($user);
        $this->assertEquals(10, $user->getLoginCount());
    }

    public function testShouldCorrectlyUpdateBooleanEntityField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => 'Oro_Bundle_UserBundle_Entity_User',
                'id' => $user->getId()
            ]),
            '{"enabled": false}'
        );

        $this->assertLastResponseStatus(Response::HTTP_OK);

        $this->refreshEntity($user);
        $this->assertFalse($user->isEnabled());
    }

    public function testShouldCorrectlyUpdateDateEntityField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => 'Oro_Bundle_UserBundle_Entity_User',
                'id' => $user->getId()
            ]),
            '{"birthday": "2000-05-05T00:00:00+0000"}'
        );

        $this->assertLastResponseStatus(Response::HTTP_OK);

        $this->refreshEntity($user);
        $this->assertEquals(new \DateTime('2000-05-05T00:00:00+0000'), $user->getBirthday());
    }

    public function testShouldCorrectlyUpdateDateTimeEntityField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => 'Oro_Bundle_UserBundle_Entity_User',
                'id' => $user->getId()
            ]),
            '{"lastLogin":"2000-05-05T01:05:05+0000"}'
        );

        $this->assertLastResponseStatus(Response::HTTP_OK);

        $this->refreshEntity($user);
        $this->assertEquals(new \DateTime('2000-05-05T01:05:05+0000'), $user->getLastLogin());
    }

    public function testShouldNotAllowChangeEntityFieldValueIfNotValid()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => 'Oro_Bundle_UserBundle_Entity_User',
                'id' => $user->getId()
            ]),
            '{"email": "test"}'
        );

        $this->assertLastResponseStatus(Response::HTTP_BAD_REQUEST);
        $this->assertLastResponseContentTypeJson();

        $content = $this->getLastResponseJsonContent();
        $this->assertEquals('Validation Failed', $content['message']);
    }

    public function testShouldNotAllowChangeEntityFieldValueIfNotValidBecauseNewValueIsEmpty()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->sendPatch(
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => 'Oro_Bundle_UserBundle_Entity_User',
                'id' => $user->getId()
            ]),
            '{"username": ""}'
        );

        $this->assertLastResponseStatus(Response::HTTP_BAD_REQUEST);
        $this->assertLastResponseContentTypeJson();

        $content = $this->getLastResponseJsonContent();
        $this->assertEquals('Validation Failed', $content['message']);
    }

    /**
     * @dataProvider setAssociationDataProvider
     */
    public function testAssociationFieldPatch(
        string $className,
        string $field,
        string|array $reference,
        int $responseCode
    ) {
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

        if ($responseCode === Response::HTTP_OK) {
            $this->getContainer()->get('doctrine')->getManager()->clear();
            $repository = $this->getContainer()->get('doctrine')->getRepository($className);
            $object = $repository->find($id);
            $accessor = PropertyAccess::createPropertyAccessor();

            if (is_array($reference)) {
                $this->assertSameSize($reference, $accessor->getValue($object, $field));
            } else {
                $this->assertEquals($reference->getId(), $accessor->getValue($object, $field)->getId());
            }
        }
        if ($responseCode === Response::HTTP_BAD_REQUEST) {
            $response = $this->getJsonResponseContent(
                $this->client->getResponse(),
                $this->client->getResponse()->getStatusCode()
            );
            $this->assertEquals('Validation Failed', $response['message']);
        }
    }

    private function sendPatch(string $url, string $content): void
    {
        $this->client->request('PATCH', $url, [], [], [], $content);
    }

    private function refreshEntity(object  $entity): void
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        $doctrine->getManager()->clear();
        $doctrine->getManager()->find(get_class($entity), $entity->getId());
    }

    public function setAssociationDataProvider(): array
    {
        return [
            'entity many to many' => [
                User::class,
                'userRoles',
                ['ROLE_TEST_1', 'ROLE_TEST_2'],
                Response::HTTP_OK
            ],
            'entity many to one' => [
                User::class,
                'owner',
                'TestBusinessUnit',
                Response::HTTP_OK
            ]
        ];
    }
}
