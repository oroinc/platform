<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Stubs;

use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\ConfigExpression\ExpressionAssemblerAwareInterface;

interface ExpressionAssemblerLayoutUpdateInterface extends LayoutUpdateInterface, ExpressionAssemblerAwareInterface
{
}
