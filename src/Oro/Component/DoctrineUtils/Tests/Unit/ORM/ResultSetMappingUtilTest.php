<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Component\DoctrineUtils\ORM\PlatformResultSetMapping;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;

class ResultSetMappingUtilTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateResultSetMapping()
    {
        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertInstanceOf(
            PlatformResultSetMapping::class,
            ResultSetMappingUtil::createResultSetMapping($platform)
        );
    }
}
