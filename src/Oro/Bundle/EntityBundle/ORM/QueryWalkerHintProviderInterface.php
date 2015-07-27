<?php

namespace Oro\Bundle\EntityBundle\ORM;

/**
 * An interface for providers of query walker hints
 */
interface QueryWalkerHintProviderInterface
{
    /**
     * Returns the list of hints of a query walker
     *
     * @param mixed $params The walker parameters
     *
     * @return array
     */
    public function getHints($params);
}
