<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

use Oro\Component\ConfigExpression\ExpressionFactoryAwareInterface;

use Oro\Component\Layout\LayoutUpdateInterface;

interface ExpressionFactoryLayoutUpdateInterface extends LayoutUpdateInterface, ExpressionFactoryAwareInterface
{
}
