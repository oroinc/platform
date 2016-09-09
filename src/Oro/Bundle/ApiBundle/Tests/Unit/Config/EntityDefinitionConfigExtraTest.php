<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class EntityDefinitionConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    public function testGetName()
    {
        $extra = new EntityDefinitionConfigExtra();
        $this->assertEquals(EntityDefinitionConfigExtra::NAME, $extra->getName());
    }

    public function testIsPropagable()
    {
        $extra = new EntityDefinitionConfigExtra();
        $this->assertTrue($extra->isPropagable());
    }

    public function testCacheKeyPartNoParameters()
    {
        $extra = new EntityDefinitionConfigExtra();
        $this->assertEquals(
            'definition',
            $extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartForSingleItemResource()
    {
        $extra = new EntityDefinitionConfigExtra('action');
        $this->assertEquals(
            'definition:action',
            $extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartForCollectionResource()
    {
        $extra = new EntityDefinitionConfigExtra('action', true);
        $this->assertEquals(
            'definition:action:collection',
            $extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartForSingleItemSubresource()
    {
        $extra = new EntityDefinitionConfigExtra('action', false, 'Test\ParentClass', 'association');
        $this->assertEquals(
            'definition:action:Test\ParentClass:association',
            $extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartForCollectionSubresource()
    {
        $extra = new EntityDefinitionConfigExtra('action', true, 'Test\ParentClass', 'association');
        $this->assertEquals(
            'definition:action:collection:Test\ParentClass:association',
            $extra->getCacheKeyPart()
        );
    }

    public function testConfigureContextNoParameters()
    {
        $extra = new EntityDefinitionConfigExtra();
        $context = new ConfigContext();
        $extra->configureContext($context);
        $this->assertNull($context->getTargetAction());
        $this->assertFalse($context->isCollection());
        $this->assertNull($context->getParentClassName());
        $this->assertNull($context->getAssociationName());
    }

    public function testConfigureContextForSingleItemResource()
    {
        $extra = new EntityDefinitionConfigExtra('action');
        $context = new ConfigContext();
        $extra->configureContext($context);
        $this->assertEquals('action', $context->getTargetAction());
        $this->assertFalse($context->isCollection());
        $this->assertNull($context->getParentClassName());
        $this->assertNull($context->getAssociationName());
    }

    public function testConfigureContextForCollectionResource()
    {
        $extra = new EntityDefinitionConfigExtra('action', true);
        $context = new ConfigContext();
        $extra->configureContext($context);
        $this->assertEquals('action', $context->getTargetAction());
        $this->assertTrue($context->isCollection());
        $this->assertNull($context->getParentClassName());
        $this->assertNull($context->getAssociationName());
    }

    public function testConfigureContextForSingleItemSubresource()
    {
        $extra = new EntityDefinitionConfigExtra('action', false, 'Test\ParentClass', 'association');
        $context = new ConfigContext();
        $extra->configureContext($context);
        $this->assertEquals('action', $context->getTargetAction());
        $this->assertFalse($context->isCollection());
        $this->assertEquals('Test\ParentClass', $context->getParentClassName());
        $this->assertEquals('association', $context->getAssociationName());
    }

    public function testConfigureContextForCollectionSubresource()
    {
        $extra = new EntityDefinitionConfigExtra('action', true, 'Test\ParentClass', 'association');
        $context = new ConfigContext();
        $extra->configureContext($context);
        $this->assertEquals('action', $context->getTargetAction());
        $this->assertTrue($context->isCollection());
        $this->assertEquals('Test\ParentClass', $context->getParentClassName());
        $this->assertEquals('association', $context->getAssociationName());
    }
}
