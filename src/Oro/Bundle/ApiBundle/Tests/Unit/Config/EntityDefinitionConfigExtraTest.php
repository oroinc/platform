<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class EntityDefinitionConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName()
    {
        $extra = new EntityDefinitionConfigExtra();
        self::assertEquals(EntityDefinitionConfigExtra::NAME, $extra->getName());
    }

    public function testIsPropagable()
    {
        $extra = new EntityDefinitionConfigExtra();
        self::assertTrue($extra->isPropagable());
    }

    public function testCacheKeyPartNoParameters()
    {
        $extra = new EntityDefinitionConfigExtra();
        self::assertEquals(
            'definition',
            $extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartForSingleItemResource()
    {
        $extra = new EntityDefinitionConfigExtra('action');
        self::assertEquals(
            'definition:action',
            $extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartForCollectionResource()
    {
        $extra = new EntityDefinitionConfigExtra('action', true);
        self::assertEquals(
            'definition:action:collection',
            $extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartForSingleItemSubresource()
    {
        $extra = new EntityDefinitionConfigExtra('action', false, 'Test\ParentClass', 'association');
        self::assertEquals(
            'definition:action:Test\ParentClass:association',
            $extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartForCollectionSubresource()
    {
        $extra = new EntityDefinitionConfigExtra('action', true, 'Test\ParentClass', 'association');
        self::assertEquals(
            'definition:action:collection:Test\ParentClass:association',
            $extra->getCacheKeyPart()
        );
    }

    public function testConfigureContextNoParameters()
    {
        $extra = new EntityDefinitionConfigExtra();
        $context = new ConfigContext();
        $extra->configureContext($context);
        self::assertNull($context->getTargetAction());
        self::assertFalse($context->isCollection());
        self::assertNull($context->getParentClassName());
        self::assertNull($context->getAssociationName());
    }

    public function testConfigureContextForSingleItemResource()
    {
        $extra = new EntityDefinitionConfigExtra('action');
        $context = new ConfigContext();
        $extra->configureContext($context);
        self::assertEquals('action', $context->getTargetAction());
        self::assertFalse($context->isCollection());
        self::assertNull($context->getParentClassName());
        self::assertNull($context->getAssociationName());
    }

    public function testConfigureContextForCollectionResource()
    {
        $extra = new EntityDefinitionConfigExtra('action', true);
        $context = new ConfigContext();
        $extra->configureContext($context);
        self::assertEquals('action', $context->getTargetAction());
        self::assertTrue($context->isCollection());
        self::assertNull($context->getParentClassName());
        self::assertNull($context->getAssociationName());
    }

    public function testConfigureContextForSingleItemSubresource()
    {
        $extra = new EntityDefinitionConfigExtra('action', false, 'Test\ParentClass', 'association');
        $context = new ConfigContext();
        $extra->configureContext($context);
        self::assertEquals('action', $context->getTargetAction());
        self::assertFalse($context->isCollection());
        self::assertEquals('Test\ParentClass', $context->getParentClassName());
        self::assertEquals('association', $context->getAssociationName());
    }

    public function testConfigureContextForCollectionSubresource()
    {
        $extra = new EntityDefinitionConfigExtra('action', true, 'Test\ParentClass', 'association');
        $context = new ConfigContext();
        $extra->configureContext($context);
        self::assertEquals('action', $context->getTargetAction());
        self::assertTrue($context->isCollection());
        self::assertEquals('Test\ParentClass', $context->getParentClassName());
        self::assertEquals('association', $context->getAssociationName());
    }
}
