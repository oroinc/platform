<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Query;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\QueryResolver;

/**
 * This query resolver modifies Data API queries in order to protect data
 * that can be retrieved via these queries.
 */
class AclProtectedQueryResolver extends QueryResolver
{
    public const SKIP_ACL_FOR_ROOT_ENTITY = 'skip_acl_for_root_entity';

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
        $options = [];
        $skipRootEntity = (bool)$config->get(self::SKIP_ACL_FOR_ROOT_ENTITY);
        if ($skipRootEntity) {
            $options[AclHelper::CHECK_ROOT_ENTITY] = false;
        }
        $this->aclHelper->apply($query, 'VIEW', $options);

        parent::resolveQuery($query, $config);
    }
}
