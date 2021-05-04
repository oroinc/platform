<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config\Tree;

use Oro\Bundle\ConfigBundle\Config\Tree\FieldNodeDefinition;

class FieldNodeDefinitionTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_NAME = 'testNodeName';
    private const TEST_TYPE = 'text';
    private const TEST_ACL  = 'acl';
    private const TEST_NEEDS_PAGE_RELOAD = true;

    private const TEST_DEFINITION = [
        'options'      => [
            'some_opt' => 'some_value'
        ],
        'type'         => self::TEST_TYPE,
        'acl_resource' => self::TEST_ACL,
        'page_reload'  => self::TEST_NEEDS_PAGE_RELOAD,
    ];

    public function testGetName()
    {
        $node = new FieldNodeDefinition(self::TEST_NAME, self::TEST_DEFINITION);
        $this->assertEquals(self::TEST_NAME, $node->getName());
    }

    public function testPriority()
    {
        $node = new FieldNodeDefinition(self::TEST_NAME, self::TEST_DEFINITION);
        $this->assertSame(0, $node->getPriority());

        $priority = 100;
        $node = new FieldNodeDefinition(self::TEST_NAME, array_merge(self::TEST_DEFINITION, [
            'priority' => $priority
        ]));
        $this->assertSame($priority, $node->getPriority());

        $priority = 255;
        $node->setPriority($priority);
        $this->assertSame($priority, $node->getPriority());
    }

    public function testGetType()
    {
        $node = new FieldNodeDefinition(self::TEST_NAME, self::TEST_DEFINITION);
        $this->assertEquals(self::TEST_TYPE, $node->getType());
    }

    public function testGetAclResource()
    {
        // acl resource specified
        $node = new FieldNodeDefinition(self::TEST_NAME, self::TEST_DEFINITION);
        $this->assertEquals(self::TEST_ACL, $node->getAclResource());

        // acl resource not specified, should return false
        $node = new FieldNodeDefinition(self::TEST_NAME, []);
        $this->assertFalse($node->getAclResource());
    }

    public function testGetOptions()
    {
        // options come from definition
        $node = new FieldNodeDefinition(self::TEST_NAME, self::TEST_DEFINITION);
        $this->assertEquals(self::TEST_ACL, $node->getAclResource());

        // options come from setter
        $options = ['another_opt' => 'another_value'];

        $node = new FieldNodeDefinition(self::TEST_NAME, []);
        $node->setOptions($options);
        $this->assertEquals($options, $node->getOptions());

        // option override
        $node->replaceOption('another_opt', 'newValue');
        $options = $node->getOptions();
        $this->assertArrayHasKey('another_opt', $options);
        $this->assertEquals('newValue', $options['another_opt']);
    }

    public function testPrepareDefinition()
    {
        $node = new FieldNodeDefinition(self::TEST_NAME, []);

        // should set default definition values
        $this->assertEquals(0, $node->getPriority());
        $this->assertIsArray($node->getOptions());
    }

    public function testPropertyPathIsApplied()
    {
        $nodeWithoutPropertyPath = new FieldNodeDefinition(self::TEST_NAME, self::TEST_DEFINITION);
        $this->assertEquals(self::TEST_NAME, $nodeWithoutPropertyPath->getPropertyPath());

        $nodeWithPropertyPath = new FieldNodeDefinition(self::TEST_NAME, array_merge(self::TEST_DEFINITION, [
            'property_path' => 'test_path'
        ]));
        $this->assertEquals('test_path', $nodeWithPropertyPath->getPropertyPath());
    }

    public function testNeedsPageReload()
    {
        $node = new FieldNodeDefinition(self::TEST_NEEDS_PAGE_RELOAD, self::TEST_DEFINITION);
        $this->assertSame(self::TEST_NEEDS_PAGE_RELOAD, $node->needsPageReload());
    }
}
