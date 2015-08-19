<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;

interface StubContextAwarePathProvider extends PathProviderInterface, ContextAwareInterface
{
}
