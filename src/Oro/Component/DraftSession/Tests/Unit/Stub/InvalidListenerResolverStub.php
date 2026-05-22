<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Stub;

/**
 * Stub for an entity listener resolver that does not implement OroEntityListenerResolver.
 * Used to test that DoctrineListenersIsolator throws when the resolver is of the wrong type.
 */
class InvalidListenerResolverStub
{
}
