<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDataTypes;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDefaultAndNull;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class DefaultAndNullTestCase extends RestJsonApiTestCase
{
    protected function sendCreateRequest(array $data, bool $assertValid = true): Response
    {
        $entityType = $this->getEntityType(TestDefaultAndNull::class);

        $data['data']['type'] = $entityType;

        return $this->post(
            ['entity' => $entityType],
            $data,
            [],
            $assertValid
        );
    }

    protected function sendUpdateRequest(int $entityId, array $data, bool $assertValid = true): Response
    {
        $entityType = $this->getEntityType(TestDefaultAndNull::class);

        $data['data']['type'] = $entityType;
        $data['data']['id'] = (string)$entityId;

        return $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data,
            [],
            $assertValid
        );
    }

    protected function loadTestEntity(int $entityId): TestDefaultAndNull
    {
        $em = $this->getEntityManager();
        $em->clear();

        $entity = $em->find(TestDefaultAndNull::class, $entityId);
        if (null === $entity) {
            throw new \RuntimeException('The entity does not exist in the database.');
        }

        return $entity;
    }

    protected function saveTestEntity(TestDefaultAndNull $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        $em->clear();
    }

    protected function isPostgreSql(): bool
    {
        return $this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }
}
