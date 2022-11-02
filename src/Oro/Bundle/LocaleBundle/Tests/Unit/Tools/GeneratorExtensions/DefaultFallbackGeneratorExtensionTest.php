<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Tools\GeneratorExtensions;

use Doctrine\Inflector\InflectorFactory;
use Nette\PhpGenerator\Parameter;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\DefaultFallbackMethodsNamesProvider;
use Oro\Bundle\LocaleBundle\Storage\EntityFallbackFieldsStorage;
use Oro\Bundle\LocaleBundle\Tools\GeneratorExtensions\DefaultFallbackGeneratorExtension;
use Oro\Component\PhpUtils\ClassGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class DefaultFallbackGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    private EntityFallbackFieldsStorage|MockObject $storage;

    private DefaultFallbackMethodsNamesProvider $defaultFallbackMethodsNamesProvider;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(EntityFallbackFieldsStorage::class);
        $this->defaultFallbackMethodsNamesProvider = new DefaultFallbackMethodsNamesProvider(
            InflectorFactory::create()->build()
        );
    }

    public function testSupports(): void
    {
        $this->storage->expects($this->once())
            ->method('getFieldMap')
            ->willReturn([
                'testClass' => []
            ]);

        $extension = new DefaultFallbackGeneratorExtension($this->storage, $this->defaultFallbackMethodsNamesProvider);
        self::assertTrue($extension->supports(['class' => 'testClass']));
    }

    public function testSupportsWithoutClass(): void
    {
        $this->storage->expects($this->never())
            ->method('getFieldMap');
        $extension = new DefaultFallbackGeneratorExtension($this->storage, $this->defaultFallbackMethodsNamesProvider);
        self::assertFalse($extension->supports([]));
    }

    public function testSupportsWithoutExtension(): void
    {
        $this->expectEmptyFieldMap();
        $extension = new DefaultFallbackGeneratorExtension($this->storage, $this->defaultFallbackMethodsNamesProvider);
        self::assertFalse($extension->supports(['class' => 'testClass']));
    }

    public function testMethodNotGenerated(): void
    {
        $this->expectException(\Nette\InvalidArgumentException::class);
        $class = new ClassGenerator('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $this->expectEmptyFieldMap();
        $extension = new DefaultFallbackGeneratorExtension($this->storage, $this->defaultFallbackMethodsNamesProvider);
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

        $this->storage->expects($this->atLeastOnce())
            ->method('getFieldMap')
            ->willReturn([
                'Test\Entity' => ['testField']
            ]);
        $extension = new DefaultFallbackGeneratorExtension($this->storage, $this->defaultFallbackMethodsNamesProvider);
        $extension->generate($schema, $class);

        $class->getMethod('getDefaultTestField');
    }

    public function testGenerateWithoutFields(): void
    {
        $class = new ClassGenerator('Test\Entity');
        $clonedClass = clone $class;

        $this->storage->expects($this->atLeastOnce())
            ->method('getFieldMap')
            ->willReturn([
                'Test\Entity' => []
            ]);
        $extension = new DefaultFallbackGeneratorExtension($this->storage, $this->defaultFallbackMethodsNamesProvider);
        $extension->generate(['class' => 'Test\Entity'], $class);

        self::assertEquals($class, $clonedClass);
        self::assertEmpty($class->getMethods());
    }

    public function testMethodGenerated(): void
    {
        $class = new ClassGenerator('Test\Entity');
        $schema = [
            'class' => 'Test\Entity'
        ];

        $this->storage->expects($this->atLeastOnce())
            ->method('getFieldMap')
            ->willReturn([
                'Test\Entity' => ['name'=> 'names']
            ]);
        $extension = new DefaultFallbackGeneratorExtension($this->storage, $this->defaultFallbackMethodsNamesProvider);
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

        $cloneLocalizedFallbackValueAssociationsMethodBody = <<<METHOD_BODY
foreach (["names"] as \$propertyName) {
    \$newCollection = new \Doctrine\Common\Collections\ArrayCollection();

    foreach (\$this->\$propertyName as \$element) {
        \$newCollection->add(clone \$element);
    }

    \$this->\$propertyName = \$newCollection;
}

return \$this;

METHOD_BODY;

        $this->assertMethod(
            $class,
            'cloneLocalizedFallbackValueAssociations',
            $cloneLocalizedFallbackValueAssociationsMethodBody,
            "Clones a collections of LocalizedFallbackValue associations."
        );
    }

    private function getParameter(string $name, ?string $type = null, bool $nullable = false): Parameter
    {
        $parameter = new Parameter($name);
        $parameter->setType($type);

        if ($nullable) {
            $parameter->setDefaultValue(null);
        }

        return $parameter;
    }

    private function assertMethod(
        ClassGenerator $class,
        string $methodName,
        string $methodBody,
        string $docblock,
        array $parameters = []
    ): void {
        self::assertTrue($class->hasMethod($methodName));

        $method = $class->getMethod($methodName);

        self::assertEquals($methodBody, $method->getBody());
        self::assertEquals($docblock, $method->getComment());
        self::assertEquals($parameters, $method->getParameters());
    }

    private function expectEmptyFieldMap(): void
    {
        $this->storage->expects($this->once())
            ->method('getFieldMap')
            ->willReturn([]);
    }
}
