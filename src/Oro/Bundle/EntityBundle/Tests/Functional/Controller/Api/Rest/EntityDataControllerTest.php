<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller\Api\Rest;

use Symfony\Component\PropertyAccess\PropertyAccess;

use FOS\RestBundle\Util\Codes;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class EntityDataControllerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([
            'Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadUserData',
            'Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadRoleData',
            'Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData'
        ]);
    }

    /**
     * @param string $className
     * @param string $field
     * @param mixed $value
     * @param mixed $expected
     * @param int $responseCode
     *
     * @dataProvider setDataProvider
     */
    public function testFieldPatch($className, $field, $value, $expected, $responseCode)
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $classNameSafe = $this->getContainer()
            ->get('oro_entity.entity_class_name_helper')->getUrlSafeClassName($className);
        $id = $user->getId();
        $fieldName = $field;
        $content = sprintf('{"%s":"%s"}', $fieldName, $value);
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
            $this->assertEquals($expected, $accessor->getValue($object, $fieldName));
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
     * @return array
     */
    public function setDataProvider()
    {
        $zone = new \DateTimeZone('UTC');

        return [
            'id blocked' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'id',
                1,
                1,
                Codes::HTTP_FORBIDDEN
            ],
            'not found' => [
                'Oro\Bundle\UserBundle\Entity\Test',
                'id',
                1,
                1,
                Codes::HTTP_INTERNAL_SERVER_ERROR
            ],
            'string' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'firstName',
                'Test1',
                'Test1',
                Codes::HTTP_NO_CONTENT
            ],
            'integer' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'loginCount',
                10,
                10,
                Codes::HTTP_NO_CONTENT
            ],
            'boolean' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'enabled',
                false,
                false,
                Codes::HTTP_NO_CONTENT
            ],
            'date' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'birthday',
                '2000-05-05T00:00:00+0000',
                new \DateTime('2000-05-05', $zone),
                Codes::HTTP_NO_CONTENT
            ],
            'datetime' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'lastLogin',
                '2000-05-05T01:05:05+0000',
                new \DateTime('2000-05-05 01:05:05', $zone),
                Codes::HTTP_NO_CONTENT
            ],
            'email' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'email',
                'test',
                'simple_user@example.com',
                Codes::HTTP_BAD_REQUEST
            ],
            'username' => [
                'Oro\Bundle\UserBundle\Entity\User',
                'username',
                '',
                'test',
                Codes::HTTP_BAD_REQUEST
            ]
        ];
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
