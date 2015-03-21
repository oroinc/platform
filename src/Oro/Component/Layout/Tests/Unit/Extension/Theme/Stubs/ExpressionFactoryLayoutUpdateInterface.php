<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Extension\Theme\Generator\ExpressionFactoryAwareInterface;

interface ExpressionFactoryLayoutUpdateInterface extends LayoutUpdateInterface, ExpressionFactoryAwareInterface
{
}
