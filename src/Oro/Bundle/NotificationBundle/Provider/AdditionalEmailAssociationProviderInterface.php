<?php

namespace Oro\Bundle\NotificationBundle\Provider;

/**
 * Defines a mechanism for adding additional associations that can be used as recipients for email notifications.
 */
interface AdditionalEmailAssociationProviderInterface
{
    /**
     * Gets definitions of additional associations.
     *
     * @param string $entityClass
     *
     * @return array [association name => [translated label, target class], ...]
     */
    public function getAssociations(string $entityClass): array;

    /**
     * Checks if this provider can get a value of the given association from the given entity.
     *
     * @param object $entity
     * @param string $associationName
     *
     * @return bool
     */
    public function isAssociationSupported($entity, string $associationName): bool;

    /**
     * Gets a value of the given association from the given entity.
     *
     * @param object $entity
     * @param string $associationName
     *
     * @return mixed
     */
    public function getAssociationValue($entity, string $associationName);
}
