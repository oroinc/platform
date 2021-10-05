<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Tools\GeneratorExtensions;

use Doctrine\Inflector\InflectorFactory;
use Nette\PhpGenerator\Parameter;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\DefaultFallbackMethodsNamesProvider;
use Oro\Bundle\LocaleBundle\Tools\GeneratorExtensions\DefaultFallbackGeneratorExtension;
use Oro\Component\PhpUtils\ClassGenerator;

class DefaultFallbackGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    private DefaultFallbackMethodsNamesProvider $defaultFallbackMethodsNamesProvider;

    protected function setUp(): void
    {
        $this->defaultFallbackMethodsNamesProvider = new DefaultFallbackMethodsNamesProvider(
            InflectorFactory::create()->build()
        );
    }

    public function testSupports(): void
    {
        $extension = new DefaultFallbackGeneratorExtension([
            'testClass' => []
        ], $this->defaultFallbackMethodsNamesProvider);
        static::assertTrue($extension->supports(['class' => 'testClass']));
    }

    public function testSupportsWithoutClass(): void
    {
        $extension = new DefaultFallbackGeneratorExtension([], $this->defaultFallbackMethodsNamesProvider);
        static::assertFalse($extension->supports([]));
    }

    public function testSupportsWithoutExtension(): void
    {
        $extension = new DefaultFallbackGeneratorExtension([], $this->defaultFallbackMethodsNamesProvider);
        static::assertFalse($extension->supports(['class' => 'testClass']));
    }

    public function testMethodNotGenerated(): void
    {
        $this->expectException(\Nette\InvalidArgumentException::class);
        $class = new ClassGenerator('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $extension = new DefaultFallbackGeneratorExtension([], $this->defaultFallbackMethodsNamesProvider);
        $extension->generate($schema, $class);

        $class->getMethod('defaultTestGetter');
    }

    public function testMethodNotGeneratedIncompleteFields(): void
    {
        $this->expectException(\TypeError::class);
        $class = new ClassGenerator('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $extension = new DefaultFallbackGeneratorExtension([
            'Test\Entity' => ['testField']
        ], $this->defaultFallbackMethodsNamesProvider);
        $extension->generate($schema, $class);

        $class->getMethod('getDefaultTestField');
    }

    public function testGenerateWithoutFields(): void
    {
        $class = new ClassGenerator('Test\Entity');
        $clonedClass = clone $class;

        $extension = new DefaultFallbackGeneratorExtension([
            'Test\Entity' => []
        ], $this->defaultFallbackMethodsNamesProvider);
        $extension->generate(['class' => 'Test\Entity'], $class);

        static::assertEquals($class, $clonedClass);
        static::assertEmpty($class->getMethods());
    }

    public function testMethodGenerated(): void
    {
        $class = new ClassGenerator('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $extension = new DefaultFallbackGeneratorExtension([
            'Test\Entity' => ['name'=> 'names']
        ], $this->defaultFallbackMethodsNamesProvider);
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
