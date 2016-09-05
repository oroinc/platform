<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Bundle\ApiBundle\Config\ActionFieldConfig;

class ActionFieldConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testClone()
    {
        $config = new ActionFieldConfig();
        $this->assertEmpty($config->toArray());

        $config->set('test', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $config->set('test_object', $objValue);

        $configClone = clone $config;

        $this->assertEquals($config, $configClone);
        $this->assertNotSame($objValue, $configClone->get('test_object'));
    }

    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new ActionFieldConfig();
        $this->assertFalse($config->has($attrName));
        $this->assertNull($config->get($attrName));

        $config->set($attrName, null);
        $this->assertFalse($config->has($attrName));
        $this->assertNull($config->get($attrName));
        $this->assertEquals([], $config->toArray());

        $config->set($attrName, false);
        $this->assertTrue($config->has($attrName));
        $this->assertFalse($config->get($attrName));
        $this->assertEquals([$attrName => false], $config->toArray());

        $config->remove($attrName);
        $this->assertFalse($config->has($attrName));
        $this->assertNull($config->get($attrName));
        $this->assertEquals([], $config->toArray());
    }

    public function testExcluded()
    {
        $config = new ActionFieldConfig();
        $this->assertFalse($config->hasExcluded());
        $this->assertFalse($config->isExcluded());

        $config->setExcluded();
        $this->assertTrue($config->hasExcluded());
        $this->assertTrue($config->isExcluded());
        $this->assertEquals(['exclude' => true], $config->toArray());

        $config->setExcluded(false);
        $this->assertTrue($config->hasExcluded());
        $this->assertFalse($config->isExcluded());
        $this->assertEquals([], $config->toArray());
    }

    public function testPropertyPath()
    {
        $config = new ActionFieldConfig();
        $this->assertFalse($config->hasPropertyPath());
        $this->assertNull($config->getPropertyPath());

        $config->setPropertyPath('path');
        $this->assertTrue($config->hasPropertyPath());
        $this->assertEquals('path', $config->getPropertyPath());
        $this->assertEquals(['property_path' => 'path'], $config->toArray());

        $config->setPropertyPath(null);
        $this->assertFalse($config->hasPropertyPath());
        $this->assertNull($config->getPropertyPath());
        $this->assertEquals([], $config->toArray());

        $config->setPropertyPath('path');
        $config->setPropertyPath('');
        $this->assertFalse($config->hasPropertyPath());
        $this->assertNull($config->getPropertyPath());
        $this->assertEquals([], $config->toArray());
    }

    public function testFormType()
    {
        $config = new ActionFieldConfig();
        $this->assertNull($config->getFormType());

        $config->setFormType('test');
        $this->assertEquals('test', $config->getFormType());
        $this->assertEquals(['form_type' => 'test'], $config->toArray());

        $config->setFormType(null);
        $this->assertNull($config->getFormType());
        $this->assertEquals([], $config->toArray());
    }

    public function testFormOptions()
    {
        $config = new ActionFieldConfig();
        $this->assertNull($config->getFormOptions());

        $config->setFormOptions(['key' => 'val']);
        $this->assertEquals(['key' => 'val'], $config->getFormOptions());
        $this->assertEquals(['form_options' => ['key' => 'val']], $config->toArray());

        $config->setFormOptions(null);
        $this->assertNull($config->getFormOptions());
        $this->assertEquals([], $config->toArray());
    }

    public function testAddFormConstraint()
    {
        $config = new ActionFieldConfig();

        $config->addFormConstraint(new NotNull());
        $this->assertEquals(['constraints' => [new NotNull()]], $config->getFormOptions());

        $config->addFormConstraint(new NotBlank());
        $this->assertEquals(['constraints' => [new NotNull(), new NotBlank()]], $config->getFormOptions());
    }
}
