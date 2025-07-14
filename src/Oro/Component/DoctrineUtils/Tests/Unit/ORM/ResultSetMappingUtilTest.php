<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Component\DoctrineUtils\ORM\PlatformResultSetMapping;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;
use PHPUnit\Framework\TestCase;

class ResultSetMappingUtilTest extends TestCase
{
    public function testCreateResultSetMapping(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);

        $this->assertInstanceOf(
            PlatformResultSetMapping::class,
            ResultSetMappingUtil::createResultSetMapping($platform)
        );
    }
}
