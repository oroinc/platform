<?php

namespace Oro\Component\ConfigExpression\Func;

use Oro\Component\ConfigExpression\AbstractExpression;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

abstract class AbstractFunction extends AbstractExpression implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;
}
