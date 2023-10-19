<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata\Extra;

use Oro\Bundle\ApiBundle\Metadata\Extra\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;

class ActionMetadataExtraTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $extra = new ActionMetadataExtra('test_action');
        self::assertEquals(ActionMetadataExtra::NAME, $extra->getName());
    }

    public function testCacheKeyPart(): void
    {
        $extra = new ActionMetadataExtra('test_action');
        self::assertEquals(
            'action:test_action',
            $extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartWithParentAction(): void
    {
        $extra = new ActionMetadataExtra('test_action');
        $extra->setParentAction('test_parent_action');
        self::assertEquals(
            'action:test_action:test_parent_action',
            $extra->getCacheKeyPart()
        );
    }

    public function testConfigureContext(): void
    {
        $extra = new ActionMetadataExtra('test_action');
        $context = new MetadataContext();
        $extra->configureContext($context);
        self::assertEquals('test_action', $context->getTargetAction());
        self::assertNull($context->getParentAction());
    }

    public function testConfigureContextWithParentAction(): void
    {
        $extra = new ActionMetadataExtra('test_action');
        $extra->setParentAction('test_parent_action');
        $context = new MetadataContext();
        $extra->configureContext($context);
        self::assertEquals('test_action', $context->getTargetAction());
        self::assertEquals('test_parent_action', $context->getParentAction());
    }
}
