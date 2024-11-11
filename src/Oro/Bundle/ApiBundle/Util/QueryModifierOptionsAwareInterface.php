<?php

namespace Oro\Bundle\ApiBundle\Util;

/**
 * The interface should be implemented by classes that implements {@see QueryModifierInterface}
 * and depends on an additional options.
 * @see AclProtectedQueryFactory::modifyQuery
 */
interface QueryModifierOptionsAwareInterface
{
    public function setOptions(?array $options): void;
}
