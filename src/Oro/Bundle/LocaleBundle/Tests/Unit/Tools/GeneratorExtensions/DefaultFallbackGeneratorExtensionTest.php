<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;

use Oro\Bundle\LocaleBundle\Entity\Localization;
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
        $schema = [
            'class' => 'Test\Entity'
        ];

        $this->extension->addDefaultMethodFields('Test\Entity', [
            'name'=> 'names',
        ]);

        $this->extension->generate($schema, $class);

        $this->assertMethod(
            $class,
            'getName',
            'return $this->getFallbackValue($this->names, $localization);',
            [
                $this->getParameter('localization', Localization::class, true),
            ]
        );

        $this->assertMethod(
            $class,
            'getDefaultName',
            'return $this->getDefaultFallbackValue($this->names);'
        );

        $this->assertMethod(
            $class,
            'setDefaultName',
            'return $this->setDefaultFallbackValue($this->names, $value);',
            [
                $this->getParameter('value'),
            ]
        );
    }

    /**
     * @param string $name
     * @param string|null $type
     * @param bool|false $nullable
     * @return PhpParameter
     */
    protected function getParameter($name, $type = null, $nullable = false)
    {
        $parameter = PhpParameter::create($name)
            ->setType($type);

        if ($nullable) {
            $parameter->setDefaultValue(null);
        }

        return $parameter;
    }

    /**
     * @param PhpClass $class
     * @param string $methodName
     * @param string $methodBody
     * @param array $parameters
     */
    protected function assertMethod(PhpClass $class, $methodName, $methodBody, array $parameters = [])
    {
        $this->assertTrue($class->hasMethod($methodName));

        /* @var $method PhpMethod */
        $method = $class->getMethod($methodName);

        $this->assertEquals($methodBody, $method->getBody());
        $this->assertEquals($parameters, $method->getParameters());
    }
}
