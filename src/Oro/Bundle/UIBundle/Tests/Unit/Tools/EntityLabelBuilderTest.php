<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

use Oro\Bundle\UIBundle\Attribute\AsSimpleEntityClassName;
use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;

class EntityLabelBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetEntityLabelTranslationKey()
    {
        $this->assertEquals(
            'acme.product.entity_label',
            EntityLabelBuilder::getEntityLabelTranslationKey('Acme\Bundle\ProductBundle\Entity\Product')
        );
    }

    public function testGetEntityPluralLabelTranslationKey()
    {
        $this->assertEquals(
            'acme.product.entity_plural_label',
            EntityLabelBuilder::getEntityPluralLabelTranslationKey('Acme\Bundle\ProductBundle\Entity\Product')
        );
    }

    public function testGetEntityDescriptionTranslationKey()
    {
        $this->assertEquals(
            'acme.product.entity_description',
            EntityLabelBuilder::getEntityDescriptionTranslationKey('Acme\Bundle\ProductBundle\Entity\Product')
        );
    }

    public function testGetFieldLabelTranslationKey()
    {
        $this->assertEquals(
            'acme.product.sell_price.label',
            EntityLabelBuilder::getFieldLabelTranslationKey('Acme\Bundle\ProductBundle\Entity\Product', 'sellPrice')
        );
    }

    public function testGetFieldDescriptionTranslationKey()
    {
        $this->assertEquals(
            'acme.product.sell_price.description',
            EntityLabelBuilder::getFieldDescriptionTranslationKey(
                'Acme\Bundle\ProductBundle\Entity\Product',
                'sellPrice'
            )
        );
    }

    /**
     * @dataProvider getTranslationKeyProvider
     */
    public function testGetTranslationKey(
        string $expected,
        string $propertyName,
        string $className,
        ?string $fieldName
    ) {
        $result = EntityLabelBuilder::getTranslationKey($propertyName, $className, $fieldName);
        $this->assertEquals($expected, $result);
    }

    public function getTranslationKeyProvider(): array
    {
        return [
            [
                'acme.test.product.entity_label',
                'label',
                'Acme\Bundle\TestBundle\Entity\Product',
                null
            ],
            [
                'acme.test.product.entity_plural_label',
                'plural_label',
                'Acme\Bundle\TestBundle\Entity\Product',
                null
            ],
            [
                'acme.product.entity_label',
                'label',
                'Acme\Bundle\ProductBundle\Entity\Product',
                null
            ],
            [
                'acme.product.entity_label',
                'label',
                'Acme\Bundle\ProductBundle\Document\Product',
                null
            ],
            [
                'acme.test.product.sell_price.label',
                'label',
                'Acme\Bundle\TestBundle\Entity\Product',
                'sellPrice'
            ],
            [
                'acme.test.product.sell_price.plural_label',
                'plural_label',
                'Acme\Bundle\TestBundle\Entity\Product',
                'sellPrice'
            ],
            [
                'acme.product.sell_price.label',
                'label',
                'Acme\Bundle\ProductBundle\Entity\Product',
                'sellPrice'
            ],
            [
                'acme.product.sell_price.label',
                'label',
                'Acme\Bundle\ProductBundle\Document\Product',
                'sellPrice'
            ],
            [
                'acme.entityproduct.sell_price.label',
                'label',
                'Acme\Bundle\EntityProductBundle\Document\EntityProduct',
                'sellPrice'
            ],
        ];
    }

    public function testExplodeClassName()
    {
        $this->assertEquals(
            ['acme', 'test', 'product'],
            EntityLabelBuilder::explodeClassName('Acme\Bundle\TestBundle\Entity\Product')
        );
        $this->assertEquals(
            ['acme', 'test', 'product'],
            EntityLabelBuilder::explodeClassName('Acme\Bundle\TestBundle\Document\Product')
        );
        $this->assertEquals(
            ['acme', 'test', 'product'],
            EntityLabelBuilder::explodeClassName('Acme\Bundle\TestBundle\Model\Product')
        );
        $this->assertEquals(
            ['acme', 'test', 'other', 'product'],
            EntityLabelBuilder::explodeClassName('Acme\Bundle\TestBundle\Other\Product')
        );
        $this->assertEquals(
            ['acme', 'test', 'product'],
            EntityLabelBuilder::explodeClassName('Acme\Bundle\TestBundle\Product')
        );
    }

    public function testExplodeClassNameWithSimpleEntityClassName()
    {
        $attributes = sprintf('#[\%s]', AsSimpleEntityClassName::class);

        $className = 'App\Entity\Product';
        ['fqcn' => $fqcn, 'uniqueSuffix' => $uniqueSuffix] = $this->declareClass($className, attributes: $attributes);
        $this->assertEquals(
            ['app', 'entity', 'product' . $uniqueSuffix],
            EntityLabelBuilder::explodeClassName($fqcn)
        );

        $className = 'App\Model\Product';
        ['fqcn' => $fqcn, 'uniqueSuffix' => $uniqueSuffix] = $this->declareClass($className, attributes: $attributes);
        $this->assertEquals(
            ['app', 'model', 'product' . $uniqueSuffix],
            EntityLabelBuilder::explodeClassName($fqcn)
        );

        $className = 'Acme\Bundle\FooBundle\Entity\Product';
        ['fqcn' => $fqcn, 'uniqueSuffix' => $uniqueSuffix] = $this->declareClass($className, attributes: $attributes);
        $this->assertEquals(
            ['acme_foo', 'entity', 'product' . $uniqueSuffix],
            EntityLabelBuilder::explodeClassName($fqcn)
        );

        $className = 'Acme\Bundle\FooBundle\Document\Product';
        ['fqcn' => $fqcn, 'uniqueSuffix' => $uniqueSuffix] = $this->declareClass($className, attributes: $attributes);
        $this->assertEquals(
            ['acme_foo', 'document', 'product' . $uniqueSuffix],
            EntityLabelBuilder::explodeClassName($fqcn)
        );

        $className = 'AcmeCorp\Model\Subfolder\SomeModel';
        ['fqcn' => $fqcn, 'uniqueSuffix' => $uniqueSuffix] = $this->declareClass($className, attributes: $attributes);
        $this->assertEquals(
            ['acme_corp', 'model', 'subfolder', 'some_model' . $uniqueSuffix],
            EntityLabelBuilder::explodeClassName($fqcn)
        );

        $className = 'AcmeCorp\Bundle\FooBarBundle\SomeModel\OtherProduct';
        ['fqcn' => $fqcn, 'uniqueSuffix' => $uniqueSuffix] = $this->declareClass($className, attributes: $attributes);
        $this->assertEquals(
            ['acme_corp_foo_bar', 'some_model', 'other_product' . $uniqueSuffix],
            EntityLabelBuilder::explodeClassName($fqcn)
        );

        $className = 'AcmeCorp\Bundle\FooBarBundle\SomeModel\DeeperDir\OtherProduct';
        ['fqcn' => $fqcn, 'uniqueSuffix' => $uniqueSuffix] = $this->declareClass($className, attributes: $attributes);
        $this->assertEquals(
            ['acme_corp_foo_bar', 'some_model', 'deeper_dir', 'other_product' . $uniqueSuffix],
            EntityLabelBuilder::explodeClassName($fqcn)
        );
    }

    /**
     * @return array ['fqcn' => string, 'uniqueSuffix' => string]
     */
    protected function declareClass(
        string $className,
        string $imports = '',
        string $attributes = '',
        string $body = ''
    ): array {
        /** @see \Oro\Bundle\ApiBundle\Tests\Unit\Stub\AbstractFormTypeExtensionStub::createUniqueInstance */
        do {
            $uniqueSuffix = \bin2hex(\random_bytes(10));
            $fqcn = sprintf('%s%s', $className, $uniqueSuffix);
        } while (class_exists($fqcn));

        $namespace = '';
        $shortName = $fqcn;
        if (\str_contains($fqcn, '\\')) {
            $parts = \explode('\\', $fqcn);
            $shortName = \array_pop($parts);
            $namespace = \implode('\\', $parts);
        }

        $definition = <<<EOT
            namespace $namespace;
            $imports
            $attributes
            class $shortName {
                $body
            };
        EOT;
        eval($definition);

        return ['fqcn' => $fqcn, 'uniqueSuffix' => $uniqueSuffix];
    }
}
