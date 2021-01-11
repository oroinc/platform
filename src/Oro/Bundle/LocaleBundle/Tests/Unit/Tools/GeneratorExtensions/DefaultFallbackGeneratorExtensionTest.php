<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Tools\GeneratorExtensions;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Nette\PhpGenerator\Parameter;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tools\GeneratorExtensions\DefaultFallbackGeneratorExtension;
use Oro\Component\PhpUtils\ClassGenerator;

class DefaultFallbackGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    private Inflector $inflector;

    protected function setUp(): void
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    public function testSupports()
    {
        $extension = new DefaultFallbackGeneratorExtension([
            'testClass' => []
        ], $this->inflector);
        static::assertTrue($extension->supports(['class' => 'testClass']));
    }

    public function testSupportsWithoutClass()
    {
        $extension = new DefaultFallbackGeneratorExtension([], $this->inflector);
        static::assertFalse($extension->supports([]));
    }

    public function testSupportsWithoutExtension()
    {
        $extension = new DefaultFallbackGeneratorExtension([], $this->inflector);
        static::assertFalse($extension->supports(['class' => 'testClass']));
    }

    public function testMethodNotGenerated()
    {
        $this->expectException(\Nette\InvalidArgumentException::class);
        $class = new ClassGenerator('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $extension = new DefaultFallbackGeneratorExtension([], $this->inflector);
        $extension->generate($schema, $class);

        $class->getMethod('defaultTestGetter');
    }

    public function testMethodNotGeneratedIncompleteFields()
    {
        $this->expectException(\TypeError::class);
        $class = new ClassGenerator('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $extension = new DefaultFallbackGeneratorExtension([
            'Test\Entity' => ['testField']
        ], $this->inflector);
        $extension->generate($schema, $class);

        $class->getMethod('getDefaultTestField');
    }

    public function testGenerateWithoutFields()
    {
        $class = new ClassGenerator('Test\Entity');
        $clonedClass = clone $class;

        $extension = new DefaultFallbackGeneratorExtension([
            'Test\Entity' => []
        ], $this->inflector);
        $extension->generate(['class' => 'Test\Entity'], $class);

        static::assertEquals($class, $clonedClass);
        static::assertEmpty($class->getMethods());
    }

    public function testMethodGenerated()
    {
        $class = new ClassGenerator('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $extension = new DefaultFallbackGeneratorExtension([
            'Test\Entity' => ['name'=> 'names']
        ], $this->inflector);
        $extension->generate($schema, $class);

        $this->assertMethod(
            $class,
            'getName',
            'return $this->getFallbackValue($this->names, $localization);' . "\n",
            "@param \Oro\Bundle\LocaleBundle\Entity\Localization|null \$localization\n"
            . "@return \Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue|null",
            [
                'localization' => $this->getParameter('localization', Localization::class, true),
            ]
        );

        $this->assertMethod(
            $class,
            'getDefaultName',
            'return $this->getDefaultFallbackValue($this->names);' . "\n",
            "@return \Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue|null"
        );

        $this->assertMethod(
            $class,
            'setDefaultName',
            'return $this->setDefaultFallbackValue($this->names, $value);' . "\n",
            "@param string \$value\n@return \$this",
            [
                'value' => $this->getParameter('value'),
            ]
        );
    }

    protected function getParameter(string $name, ?string $type = null, bool $nullable = false): Parameter
    {
        $parameter = new Parameter($name);
        $parameter->setType($type);

        if ($nullable) {
            $parameter->setDefaultValue(null);
        }

        return $parameter;
    }

    protected function assertMethod(
        ClassGenerator $class,
        string $methodName,
        string $methodBody,
        string $docblock,
        array $parameters = []
    ): void {
        static::assertTrue($class->hasMethod($methodName));

        $method = $class->getMethod($methodName);

        static::assertEquals($methodBody, $method->getBody());
        static::assertEquals($docblock, $method->getComment());
        static::assertEquals($parameters, $method->getParameters());
    }
}
