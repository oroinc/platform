<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Isolator;

use Oro\Component\DraftSession\Isolator\UnitOfWorkSnapshot;
use PHPUnit\Framework\TestCase;

final class UnitOfWorkSnapshotTest extends TestCase
{
    public function testGetStateReturnsStatePassedToConstructor(): void
    {
        $state = [
            'identityMap' => ['key' => 'value'],
            'entityInsertions' => [1, 2, 3],
            'orphanRemovals' => [],
        ];

        $snapshot = new UnitOfWorkSnapshot($state);

        self::assertSame($state, $snapshot->getState());
    }
}
