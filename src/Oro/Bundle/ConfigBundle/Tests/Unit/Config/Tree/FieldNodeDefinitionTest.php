<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config\Tree;

use Oro\Bundle\ConfigBundle\Config\Tree\FieldNodeDefinition;

class FieldNodeDefinitionTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAME = 'testNodeName';
    const TEST_TYPE = 'text';
    const TEST_ACL  = 'acl';

    protected $testDefinition = array(
        'options'      => array(
            'some_opt' => 'some_value'
        ),
        'type'         => self::TEST_TYPE,
        'acl_resource' => self::TEST_ACL
    );

    public function testGetType()
    {
        $node = new FieldNodeDefinition(self::TEST_NAME, $this->testDefinition);

        $this->assertEquals(self::TEST_TYPE, $node->getType());
    }

    public function testGetAclResource()
    {
        // acl resource specified
        $node = new FieldNodeDefinition(self::TEST_NAME, $this->testDefinition);
        $this->assertEquals(self::TEST_ACL, $node->getAclResource());

        // acl resource not specified, should return false
        $node = new FieldNodeDefinition(self::TEST_NAME, array());
        $this->assertFalse($node->getAclResource());
    }

    public function testGetOptions()
    {
        // options come from definition
        $node = new FieldNodeDefinition(self::TEST_NAME, $this->testDefinition);
        $this->assertEquals(self::TEST_ACL, $node->getAclResource());

        // options come from setter
        $options = array('another_opt' => 'another_value');

        $node = new FieldNodeDefinition(self::TEST_NAME, array());
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
        $node = new FieldNodeDefinition(self::TEST_NAME, array());

        // should set default definition values
        $this->assertEquals(0, $node->getPriority());
        $this->assertInternalType('array', $node->getOptions());
    }
}
