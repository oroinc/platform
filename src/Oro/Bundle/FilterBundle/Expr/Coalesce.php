<?php

namespace Oro\Bundle\FilterBundle\Expr;

use Doctrine\ORM\Query\Expr\Base;

class Coalesce extends Base
{
    /**
     * {@inheritdoc}
     */
    protected $preSeparator = 'COALESCE(';

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->preSeparator . implode($this->separator, $this->parts) . $this->postSeparator;
    }
}
