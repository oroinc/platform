<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ExcludeCustomFieldsConfigExtra;

class ExcludeCustomFieldsConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName()
    {
        $extra = new ExcludeCustomFieldsConfigExtra();
        self::assertEquals(ExcludeCustomFieldsConfigExtra::NAME, $extra->getName());
    }

    public function testIsPropagable()
    {
        $extra = new ExcludeCustomFieldsConfigExtra();
        self::assertTrue($extra->isPropagable());
    }

    public function testIsExclude()
    {
        $extra = new ExcludeCustomFieldsConfigExtra();
        self::assertTrue($extra->isExclude());
    }

    public function testIsExcludeWhenExcludeWasNotRequested()
    {
        $extra = new ExcludeCustomFieldsConfigExtra(false);
        self::assertFalse($extra->isExclude());
    }

    public function testCacheKeyPart()
    {
        $extra = new ExcludeCustomFieldsConfigExtra();
        self::assertEquals(ExcludeCustomFieldsConfigExtra::NAME, $extra->getCacheKeyPart());
    }

    public function testCacheKeyPartWhenExcludeWasNotRequested()
    {
        $extra = new ExcludeCustomFieldsConfigExtra(false);
        self::assertNull($extra->getCacheKeyPart());
    }
}
