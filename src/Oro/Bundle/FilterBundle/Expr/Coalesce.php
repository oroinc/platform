<?php

namespace Oro\Bundle\FilterBundle\Expr;

use Doctrine\ORM\Query\Expr\Base;

/**
 * ORM expression class for the SQL `COALESCE` function.
 *
 * This class extends Doctrine's {@see Base} expression class to provide a specialized
 * representation of the `COALESCE` SQL function. `COALESCE` returns the first non-null
 * value from a list of expressions, making it useful for handling nullable fields
 * in filter expressions and ensuring fallback values are used when primary values
 * are not available.
 */
class Coalesce extends Base
{
    protected $preSeparator = 'COALESCE(';

    #[\Override]
    public function __toString()
    {
        return $this->preSeparator . implode($this->separator, $this->parts) . $this->postSeparator;
    }
}
