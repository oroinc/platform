<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tools\GeneratorExtensions\DefaultFallbackGeneratorExtension;

class DefaultFallbackGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testSupports()
    {
        $extension = new DefaultFallbackGeneratorExtension([
            'testClass' => []
        ]);
        $this->assertTrue($extension->supports(['class' => 'testClass']));
    }

    public function testSupportsWithoutClass()
    {
        $extension = new DefaultFallbackGeneratorExtension([]);
        $this->assertFalse($extension->supports([]));
    }

    public function testSupportsWithoutExtension()
    {
        $extension = new DefaultFallbackGeneratorExtension([]);
        $this->assertFalse($extension->supports(['class' => 'testClass']));
    }

    public function testMethodNotGenerated()
    {
        $this->expectException(\InvalidArgumentException::class);
        $class = PhpClass::create('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $extension = new DefaultFallbackGeneratorExtension([]);
        $extension->generate($schema, $class);

        $class->getMethod('defaultTestGetter');
    }

    public function testMethodNotGeneratedIncompleteFields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $class = PhpClass::create('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $extension = new DefaultFallbackGeneratorExtension([
            'Test\Entity' => ['testField']
        ]);
        $extension->generate($schema, $class);

        $class->getMethod('getDefaultTestField');
    }

    public function testGenerateWithoutFields()
    {
        $class = PhpClass::create('Test\Entity');
        $clonedClass = clone $class;

        $extension = new DefaultFallbackGeneratorExtension([
            'Test\Entity' => []
        ]);
        $extension->generate(['class' => 'Test\Entity'], $class);

        $this->assertEquals($class, $clonedClass);
        $this->assertEmpty($class->getMethods());
    }

    public function testMethodGenerated()
    {
        $class = PhpClass::create('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $extension = new DefaultFallbackGeneratorExtension([
            'Test\Entity' => ['name'=> 'names']
        ]);
        $extension->generate($schema, $class);

        $this->assertMethod(
            $class,
            'getName',
            'return $this->getFallbackValue($this->names, $localization);',
            "/**\n * @param Localization|null \$localization\n * @return LocalizedFallbackValue|null\n */",
            [
                $this->getParameter('localization', Localization::class, true),
            ]
        );

        $this->assertMethod(
            $class,
            'getDefaultName',
            'return $this->getDefaultFallbackValue($this->names);',
            "/**\n * @return LocalizedFallbackValue|null\n */"
        );

        $this->assertMethod(
            $class,
            'setDefaultName',
            'return $this->setDefaultFallbackValue($this->names, $value);',
            "/**\n * @param string \$value\n * @return \$this\n */",
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
     * @param string $docblock
     * @param array $parameters
     */
    protected function assertMethod(PhpClass $class, $methodName, $methodBody, $docblock, array $parameters = [])
    {
        $this->assertTrue($class->hasMethod($methodName));

        /* @var $method PhpMethod */
        $method = $class->getMethod($methodName);

        $this->assertEquals($methodBody, $method->getBody());
        $this->assertEquals($docblock, $method->getDocblock());
        $this->assertEquals($parameters, $method->getParameters());
    }
}
