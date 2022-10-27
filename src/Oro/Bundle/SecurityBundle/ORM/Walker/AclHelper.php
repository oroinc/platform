<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

/**
 * This class adds the AccessRuleWalker tree walker with context to query to add ACL restrictions.
 *
 * @see \Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker
 */
class AclHelper
{
    public const CHECK_ROOT_ENTITY = 'checkRootEntity';
    public const CHECK_RELATIONS   = 'checkRelations';

    /** @var AccessRuleWalkerContextFactoryInterface */
    private $contextFactory;

    public function __construct(AccessRuleWalkerContextFactoryInterface $contextFactory)
    {
        $this->contextFactory = $contextFactory;
    }

    /**
     * Protects a query by ACL.
     *
     * @param Query|QueryBuilder $query      The query to be protected
     * @param string             $permission The permission to apply
     * @param array              $options    Additional options for access rules.
     *                                       The following options are implemented out-of-the-box:
     *                                       * "checkRootEntity" determined whether the root entity should be
     *                                         protected; default value is TRUE
     *                                       * "checkRelations" determined whether entities associated with
     *                                         the root entity should be protected; default value is TRUE
     *                                       To find all possible options see classes that implement
     *                                       Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface
     *
     * @return Query
     */
    public function apply($query, string $permission = 'VIEW', array $options = [])
    {
        $context = $this->contextFactory->createContext($permission);
        foreach ($options as $optionName => $value) {
            $context->setOption($optionName, $value);
        }

        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        QueryUtil::addTreeWalker($query, AccessRuleWalker::class);
        $query->setHint(AccessRuleWalker::CONTEXT, $context);

        return $query;
    }
}
