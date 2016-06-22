<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use Oro\Bundle\LocaleBundle\Tools\GeneratorExtensions\DefaultFallbackGeneratorExtension;

class DefaultFallbackGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var DefaultFallbackGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->extension = new DefaultFallbackGeneratorExtension();
    }

    public function testSupports()
    {
        $this->assertFalse(
            $this->extension->supports([])
        );

        $this->extension->addDefaultMethodFields('testClass', []);
        $this->assertTrue(
            $this->extension->supports([
                'class' => 'testClass'
            ])
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMethodNotGenerated()
    {
        $class = PhpClass::create('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $this->extension->generate($schema, $class);

        $class->getMethod('defaultTestGetter');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMethodNotGeneratedIncompleteFields()
    {
        $class = PhpClass::create('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $this->extension->addDefaultMethodFields('Test\Entity', [
            'testField'
        ]);

        $this->extension->generate($schema, $class);

        $class->getMethod('getDefaultTestField');
    }

    public function testMethodGenerated()
    {
        $class = PhpClass::create('Test\Entity');
        $fieldName = 'testFields';
        $schema = [
            'class' => 'Test\Entity'
        ];

        $this->extension->addDefaultMethodFields('Test\Entity', [
            'testField'=> $fieldName
        ]);

        $expectedBody = [
            '$values = $this->'. $fieldName .
            '->filter(function (\Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue $value) {',
            '   return null === $value->getLocalization();',
            '});',
            'if ($values->count() > 1) {',
            '   throw new \LogicException(\'There must be only one default localized fallback value\');',
            '} elseif ($values->count() === 1) {',
            '   return $values->first();',
            '}',
            'return null;'
        ];

        $expectedBody = implode("\n", $expectedBody);

        $this->extension->generate($schema, $class);

        /* @var PhpMethod $method */
        $method = $class->getMethod('getDefaultTestField');
        $this->assertEquals($expectedBody, $method->getBody());
    }
}
