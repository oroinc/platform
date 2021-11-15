<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

class AccessLevelTest extends \PHPUnit\Framework\TestCase
{
    public function testConstantValues()
    {
        $this->assertEquals(-1, AccessLevel::UNKNOWN);
        $this->assertEquals(0, AccessLevel::NONE_LEVEL);
        $this->assertGreaterThan(AccessLevel::NONE_LEVEL, AccessLevel::BASIC_LEVEL);
        $this->assertGreaterThan(AccessLevel::BASIC_LEVEL, AccessLevel::LOCAL_LEVEL);
        $this->assertGreaterThan(AccessLevel::LOCAL_LEVEL, AccessLevel::DEEP_LEVEL);
        $this->assertGreaterThan(AccessLevel::DEEP_LEVEL, AccessLevel::GLOBAL_LEVEL);
        $this->assertGreaterThan(AccessLevel::GLOBAL_LEVEL, AccessLevel::SYSTEM_LEVEL);
    }

    public function testAllAccessLevelNames()
    {
        $this->assertEquals(['BASIC', 'LOCAL', 'DEEP', 'GLOBAL', 'SYSTEM'], AccessLevel::$allAccessLevelNames);
    }
}
