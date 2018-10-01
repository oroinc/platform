<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDefaultAndNull;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class DefaultAndNullTestCase extends RestJsonApiTestCase
{
    /**
     * @param array $data
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function sendCreateRequest(array $data, $assertValid = true)
    {
        $entityType = $this->getEntityType(TestDefaultAndNull::class);

        $data['data']['type'] = $entityType;

        $response = $this->post(
            ['entity' => $entityType],
            $data,
            [],
            $assertValid
        );

        return $response;
    }

    /**
     * @param int   $entityId
     * @param array $data
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function sendUpdateRequest($entityId, array $data, $assertValid = true)
    {
        $entityType = $this->getEntityType(TestDefaultAndNull::class);

        $data['data']['type'] = $entityType;
        $data['data']['id'] = (string)$entityId;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data,
            [],
            $assertValid
        );

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
