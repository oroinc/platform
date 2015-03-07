<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Stubs;

use Oro\Component\Layout\ContextAwareInterface;
use Oro\Bundle\LayoutBundle\Layout\Loader\VoterInterface;

interface StubContextAwareVoter extends VoterInterface, ContextAwareInterface
{
}
