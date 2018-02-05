<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDefaultAndNull;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class DefaultAndNullTestCase extends RestJsonApiTestCase
{
    /**
     * @param array $data
     * @param int   $expectedStatusCode
     *
     * @return Response
     */
    protected function sendCreateRequest(array $data, $expectedStatusCode = 201)
    {
        $entityType = $this->getEntityType(TestDefaultAndNull::class);

        $data['data']['type'] = $entityType;

        $response = $this->post(
            ['entity' => $entityType],
            $data,
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, $expectedStatusCode);

        return $response;
    }

    /**
     * @param int   $entityId
     * @param array $data
     * @param int   $expectedStatusCode
     *
     * @return Response
     */
    protected function sendUpdateRequest($entityId, array $data, $expectedStatusCode = 200)
    {
        $entityType = $this->getEntityType(TestDefaultAndNull::class);

        $data['data']['type'] = $entityType;
        $data['data']['id'] = (string)$entityId;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data,
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, $expectedStatusCode);

        return $response;
    }

    /**
     * @param int $entityId
     *
     * @return TestDefaultAndNull
     */
    protected function loadTestEntity($entityId)
    {
        $em = $this->getEntityManager();
        $em->clear();

        $entity = $em->find(TestDefaultAndNull::class, $entityId);
        if (null === $entity) {
            throw new \RuntimeException('The entity does not exist in the database.');
        }

        return $entity;
    }

    /**
     * @param TestDefaultAndNull $entity
     */
    protected function saveTestEntity(TestDefaultAndNull $entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        $em->clear();
    }

    /**
     * @return bool
     */
    protected function isPostgreSql()
    {
        return $this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }
}
