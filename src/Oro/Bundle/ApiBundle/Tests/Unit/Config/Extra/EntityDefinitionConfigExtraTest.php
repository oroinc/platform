<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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

    public function testConfigureContextAndAttributesForNoParameters()
    {
        $extra = new EntityDefinitionConfigExtra();
        $context = new ConfigContext();

        $extra->configureContext($context);
        self::assertSame('', $context->getTargetAction());
        self::assertFalse($context->isCollection());
        self::assertSame('', $context->getParentClassName());
        self::assertSame('', $context->getAssociationName());

        self::assertNull($extra->getAction());
        self::assertFalse($extra->isCollection());
        self::assertNull($extra->getParentClassName());
        self::assertNull($extra->getAssociationName());
    }

    public function testConfigureContextAndAttributesForSingleItemResource()
    {
        $extra = new EntityDefinitionConfigExtra('action');
        $context = new ConfigContext();

        $extra->configureContext($context);
        self::assertEquals('action', $context->getTargetAction());
        self::assertFalse($context->isCollection());
        self::assertSame('', $context->getParentClassName());
        self::assertSame('', $context->getAssociationName());

        self::assertEquals('action', $extra->getAction());
        self::assertFalse($extra->isCollection());
        self::assertNull($extra->getParentClassName());
        self::assertNull($extra->getAssociationName());
    }

    public function testConfigureContextAndAttributesForCollectionResource()
    {
        $extra = new EntityDefinitionConfigExtra('action', true);
        $context = new ConfigContext();

        $extra->configureContext($context);
        self::assertEquals('action', $context->getTargetAction());
        self::assertTrue($context->isCollection());
        self::assertSame('', $context->getParentClassName());
        self::assertSame('', $context->getAssociationName());

        self::assertEquals('action', $extra->getAction());
        self::assertTrue($extra->isCollection());
        self::assertNull($extra->getParentClassName());
        self::assertNull($extra->getAssociationName());
    }

    public function testConfigureContextAndAttributesForSingleItemSubresource()
    {
        $extra = new EntityDefinitionConfigExtra('action', false, 'Test\ParentClass', 'association');
        $context = new ConfigContext();

        $extra->configureContext($context);
        self::assertEquals('action', $context->getTargetAction());
        self::assertFalse($context->isCollection());
        self::assertEquals('Test\ParentClass', $context->getParentClassName());
        self::assertEquals('association', $context->getAssociationName());

        self::assertEquals('action', $extra->getAction());
        self::assertFalse($extra->isCollection());
        self::assertEquals('Test\ParentClass', $extra->getParentClassName());
        self::assertEquals('association', $extra->getAssociationName());
    }

    public function testConfigureContextAndAttributesForCollectionSubresource()
    {
        $extra = new EntityDefinitionConfigExtra('action', true, 'Test\ParentClass', 'association');
        $context = new ConfigContext();

        $extra->configureContext($context);
        self::assertEquals('action', $context->getTargetAction());
        self::assertTrue($context->isCollection());
        self::assertEquals('Test\ParentClass', $context->getParentClassName());
        self::assertEquals('association', $context->getAssociationName());

        self::assertEquals('action', $extra->getAction());
        self::assertTrue($extra->isCollection());
        self::assertEquals('Test\ParentClass', $extra->getParentClassName());
        self::assertEquals('association', $extra->getAssociationName());
    }
}
