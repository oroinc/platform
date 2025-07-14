<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;
use PHPUnit\Framework\TestCase;

class EntityLabelBuilderTest extends TestCase
{
    public function testGetEntityLabelTranslationKey(): void
    {
        $this->assertEquals(
            'acme.product.entity_label',
            EntityLabelBuilder::getEntityLabelTranslationKey('Acme\Bundle\ProductBundle\Entity\Product')
        );
    }

    public function testGetEntityPluralLabelTranslationKey(): void
    {
        $this->assertEquals(
            'acme.product.entity_plural_label',
            EntityLabelBuilder::getEntityPluralLabelTranslationKey('Acme\Bundle\ProductBundle\Entity\Product')
        );
    }

    public function testGetEntityDescriptionTranslationKey(): void
    {
        $this->assertEquals(
            'acme.product.entity_description',
            EntityLabelBuilder::getEntityDescriptionTranslationKey('Acme\Bundle\ProductBundle\Entity\Product')
        );
    }

    public function testGetFieldLabelTranslationKey(): void
    {
        $this->assertEquals(
            'acme.product.sell_price.label',
            EntityLabelBuilder::getFieldLabelTranslationKey('Acme\Bundle\ProductBundle\Entity\Product', 'sellPrice')
        );
    }

    public function testGetFieldDescriptionTranslationKey(): void
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
    ): void {
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
}
