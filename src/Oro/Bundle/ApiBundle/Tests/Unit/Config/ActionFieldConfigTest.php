<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionFieldConfig;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class ActionFieldConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testClone()
    {
        $config = new ActionFieldConfig();
        self::assertEmpty($config->toArray());

        $config->set('test', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $config->set('test_object', $objValue);

        $configClone = clone $config;

        self::assertEquals($config, $configClone);
        self::assertNotSame($objValue, $configClone->get('test_object'));
    }

    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new ActionFieldConfig();
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertSame([], $config->keys());

        $config->set($attrName, null);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertEquals([], $config->toArray());
        self::assertSame([], $config->keys());

        $config->set($attrName, false);
        self::assertTrue($config->has($attrName));
        self::assertFalse($config->get($attrName));
        self::assertEquals([$attrName => false], $config->toArray());
        self::assertEquals([$attrName], $config->keys());

        $config->remove($attrName);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertSame([], $config->toArray());
        self::assertSame([], $config->keys());
    }

    public function testExcluded()
    {
        $config = new ActionFieldConfig();
        self::assertFalse($config->hasExcluded());
        self::assertFalse($config->isExcluded());

        $config->setExcluded();
        self::assertTrue($config->hasExcluded());
        self::assertTrue($config->isExcluded());
        self::assertEquals(['exclude' => true], $config->toArray());

        $config->setExcluded(false);
        self::assertTrue($config->hasExcluded());
        self::assertFalse($config->isExcluded());
        self::assertEquals([], $config->toArray());
    }

    public function testPropertyPath()
    {
        $config = new ActionFieldConfig();
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals('default', $config->getPropertyPath('default'));

        $config->setPropertyPath('path');
        self::assertTrue($config->hasPropertyPath());
        self::assertEquals('path', $config->getPropertyPath());
        self::assertEquals('path', $config->getPropertyPath('default'));
        self::assertEquals(['property_path' => 'path'], $config->toArray());

        $config->setPropertyPath(null);
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals([], $config->toArray());

        $config->setPropertyPath('path');
        $config->setPropertyPath('');
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals('default', $config->getPropertyPath('default'));
        self::assertEquals([], $config->toArray());
    }

    public function testDirection()
    {
        $config = new ActionFieldConfig();
        self::assertFalse($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());

        $config->setDirection('input-only');
        self::assertTrue($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertFalse($config->isOutput());
        self::assertEquals(['direction' => 'input-only'], $config->toArray());

        $config->setDirection('output-only');
        self::assertTrue($config->hasDirection());
        self::assertFalse($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals(['direction' => 'output-only'], $config->toArray());

        $config->setDirection('bidirectional');
        self::assertTrue($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals(['direction' => 'bidirectional'], $config->toArray());

        $config->setDirection(null);
        self::assertFalse($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals([], $config->toArray());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The possible values for the direction are "input-only", "output-only" or "bidirectional".
     */
    // @codingStandardsIgnoreEnd
    public function testSetInvalidDirection()
    {
        $config = new ActionFieldConfig();

        $config->setDirection('another');
    }

    public function testFormType()
    {
        $config = new ActionFieldConfig();
        self::assertNull($config->getFormType());

        $config->setFormType('test');
        self::assertEquals('test', $config->getFormType());
        self::assertEquals(['form_type' => 'test'], $config->toArray());

        $config->setFormType(null);
        self::assertNull($config->getFormType());
        self::assertEquals([], $config->toArray());
    }

    public function testFormOptions()
    {
        $config = new ActionFieldConfig();
        self::assertNull($config->getFormOptions());

        $config->setFormOptions(['key' => 'val']);
        self::assertEquals(['key' => 'val'], $config->getFormOptions());
        self::assertEquals(['form_options' => ['key' => 'val']], $config->toArray());

        $config->setFormOptions(null);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
    }

    public function testSetFormOption()
    {
        $config = new ActionFieldConfig();

        $config->setFormOption('option1', 'value1');
        $config->setFormOption('option2', 'value2');
        self::assertEquals(
            ['option1' => 'value1', 'option2' => 'value2'],
            $config->getFormOptions()
        );

        $config->setFormOption('option1', 'newValue');
        self::assertEquals(
            ['option1' => 'newValue', 'option2' => 'value2'],
            $config->getFormOptions()
        );
    }

    public function testFormConstraints()
    {
        $config = new ActionFieldConfig();

        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormConstraints());

        $config->addFormConstraint(new NotNull());
        self::assertEquals(['constraints' => [new NotNull()]], $config->getFormOptions());
        self::assertEquals([new NotNull()], $config->getFormConstraints());

        $config->addFormConstraint(new NotBlank());
        self::assertEquals(['constraints' => [new NotNull(), new NotBlank()]], $config->getFormOptions());
        self::assertEquals([new NotNull(), new NotBlank()], $config->getFormConstraints());
    }
}
