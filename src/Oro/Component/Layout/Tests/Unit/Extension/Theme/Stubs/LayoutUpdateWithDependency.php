<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

use Oro\Component\Layout\LayoutUpdateInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface LayoutUpdateWithDependency extends LayoutUpdateInterface, ContainerAwareInterface
{
}
