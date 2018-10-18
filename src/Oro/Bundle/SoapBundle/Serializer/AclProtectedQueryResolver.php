<?php

namespace Oro\Bundle\SoapBundle\Serializer;

use Doctrine\ORM\Query;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\QueryResolver;

/**
 * This query resolver modifies queries used by the entity serializer in order to protect data
 * that can be retrieved via these queries.
 */
class AclProtectedQueryResolver extends QueryResolver
{
    /** @var AclHelper */
    private $aclHelper;

    /**
     * @param QueryHintResolverInterface $queryHintResolver
     * @param AclHelper                  $aclHelper
     */
    public function __construct(QueryHintResolverInterface $queryHintResolver, AclHelper $aclHelper)
    {
        parent::__construct($queryHintResolver);
        $this->aclHelper = $aclHelper;
    }


    /**
     * {@inheritdoc}
     */
    public function resolveQuery(Query $query, EntityConfig $config)
    {
        $query = $this->aclHelper->apply($query);
        parent::resolveQuery($query, $config);
    }
}
