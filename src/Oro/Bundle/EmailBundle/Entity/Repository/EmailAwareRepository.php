<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;

/**
 * Defines the contract for repositories that provide email address queries.
 *
 * Repositories implementing this interface must provide methods to retrieve primary and secondary
 * email addresses for entities, supporting filtering by excluded emails and search queries.
 */
interface EmailAwareRepository
{
    /**
     * @param string $fullNameQueryPart
     * @param array $excludedEmailNames
     * @param string|null $query
     *
     * @return QueryBuilder With preselected: email, name, entityId, organization
     */
    public function getPrimaryEmailsQb($fullNameQueryPart, array $excludedEmailNames = [], $query = null);

    /**
     * @param string $fullNameQueryPart
     * @param array $excludedEmailNames
     * @param string|null $query
     *
     * @return QueryBuilder With preselected: email, name, entityId, organization
     */
    public function getSecondaryEmailsQb($fullNameQueryPart, array $excludedEmailNames = [], $query = null);
}
