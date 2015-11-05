<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\Query\SqlWalker as BaseSqlWalker;

/**
 * The SqlWalker extends Doctrine\ORM\Query\SqlWalker because TreeWalkerChain rewrite queryComponents by CustomWalker.
 * However it should merge queryComponents array in TreeWalkerChain with queryComponents which return from CustomWalkers
 */
class SqlWalker extends BaseSqlWalker
{
    const ORO_ACL_QUERY_COMPONENTS = 'oro_acl.query_components';

    /**
     * {@inheritDoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        if ($query->hasHint(self::ORO_ACL_QUERY_COMPONENTS)) {
            $hintQueryComponents = $query->getHint(self::ORO_ACL_QUERY_COMPONENTS);

            if (!empty($hintQueryComponents)) {
                $queryComponents = array_merge($queryComponents, $hintQueryComponents);
            }
        }

        parent::__construct($query, $parserResult, $queryComponents);
    }
}
