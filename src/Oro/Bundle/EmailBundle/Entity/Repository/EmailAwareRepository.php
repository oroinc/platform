<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;

interface EmailAwareRepository
{
    /**
     * @param string $fullNameQueryPart
     * @param array $excludedEmails
     * @param string|null $query
     *
     * @return QueryBuilder
     */
    public function getPrimaryEmailsQb($fullNameQueryPart, array $excludedEmails = [], $query = null);

    /**
     * @param string $fullNameQueryPart
     * @param array $excludedEmails
     * @param string|null $query
     *
     * @return QueryBuilder
     */
    public function getSecondaryEmailsQb($fullNameQueryPart, array $excludedEmails = [], $query = null);
}
