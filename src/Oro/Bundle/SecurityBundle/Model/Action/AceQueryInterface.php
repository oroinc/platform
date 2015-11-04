<?php

namespace Oro\Bundle\SecurityBundle\Model\Action;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SecurityBundle\Entity\AclClass;

interface AceQueryInterface
{
    /**
     * Create Doctrine Query Builder to remove ACE out of entity share scope
     *
     * @param AclClass $aclClass - Entity AclClass that to define ACE which should be removed
     * @param array    $removeScopes - Array name of share scopes that to define SIDs
     * @return QueryBuilder
     */
    public function getRemoveAceQueryBuilder(AclClass $aclClass, array $removeScopes);
}
