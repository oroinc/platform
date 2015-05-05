<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Component\Layout\LayoutUpdateInterface;

interface LayoutUpdateWithDependency extends LayoutUpdateInterface, ContainerAwareInterface
{
}
