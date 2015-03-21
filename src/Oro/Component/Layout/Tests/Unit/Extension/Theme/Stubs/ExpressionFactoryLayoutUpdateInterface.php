<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Stubs;

use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Bundle\LayoutBundle\Layout\Generator\ExpressionFactoryAwareInterface;

interface ExpressionFactoryLayoutUpdateInterface extends LayoutUpdateInterface, ExpressionFactoryAwareInterface
{
}
