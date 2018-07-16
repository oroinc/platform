<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

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

    public function testGetFieldLabelTranslationKey()
    {
        $this->assertEquals(
            'acme.product.sell_price.label',
            EntityLabelBuilder::getFieldLabelTranslationKey('Acme\Bundle\ProductBundle\Entity\Product', 'sellPrice')
        );
    }

    /**
     * @dataProvider getTranslationKeyProvider
     */
    public function testGetTranslationKey($expected, $propertyName, $className, $fieldName)
    {
        $result = EntityLabelBuilder::getTranslationKey($propertyName, $className, $fieldName);
        $this->assertEquals($expected, $result);
    }

    public function getTranslationKeyProvider()
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
