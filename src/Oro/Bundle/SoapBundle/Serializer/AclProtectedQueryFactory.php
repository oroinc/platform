<?php

namespace Oro\Bundle\SoapBundle\Serializer;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\QueryFactory as ParentFactory;

class AclProtectedQueryFactory extends ParentFactory
{
    /** @var AclHelper */
    protected $aclHelper;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        QueryHintResolverInterface $queryHintResolver,
        AclHelper $aclHelper
    ) {
        $this->aclHelper = $aclHelper;
        parent::__construct($doctrineHelper, $queryHintResolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(QueryBuilder $qb, EntityConfig $config)
    {
        // protect query by ACL helper
        $query = $this->aclHelper->apply($qb);
        $this->queryHintResolver->resolveHints($query, $config->getHints());

        return $query;
    }
}
