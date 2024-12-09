<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;

class DescriptionsConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    public function testDocumentationAction(): void
    {
        $extra = new DescriptionsConfigExtra();
        self::assertNull($extra->getDocumentationAction());

        $documentationAction = 'some_action';
        $extra = new DescriptionsConfigExtra($documentationAction);
        self::assertEquals($documentationAction, $extra->getDocumentationAction());
    }

    public function testGetName(): void
    {
        $extra = new DescriptionsConfigExtra();
        self::assertEquals(DescriptionsConfigExtra::NAME, $extra->getName());
    }

    public function testIsPropagable(): void
    {
        $extra = new DescriptionsConfigExtra();
        self::assertFalse($extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        $extra = new DescriptionsConfigExtra();
        self::assertEquals(DescriptionsConfigExtra::NAME, $extra->getCacheKeyPart());
    }

    public function testCacheKeyPartWithDocumentationAction(): void
    {
        $extra = new DescriptionsConfigExtra('some_action');
        self::assertEquals(DescriptionsConfigExtra::NAME . ':some_action', $extra->getCacheKeyPart());
    }
}
