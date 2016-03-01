<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;

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
