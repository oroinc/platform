<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Type\CompoundObjectType;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\NameContainerType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class CompoundObjectTypeTest extends TypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                ['test_name_container' => new NameContainerType()],
                []
            )
        ];
    }

    public function testBuildFormForField()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('name'));

        $config = new EntityDefinitionConfig();
        $config->addField('name');

        $data = new Entity\User();
        $form = $this->factory->create(
            new CompoundObjectType(new FormHelper($this->factory)),
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config,
            ]
        );
        $form->submit(['name' => 'testName']);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('testName', $data->getName());
    }

    public function testBuildFormForRenamedField()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('renamedName'))
            ->setPropertyPath('name');

        $config = new EntityDefinitionConfig();
        $config->addField('renamedName')->setPropertyPath('name');

        $data = new Entity\User();
        $form = $this->factory->create(
            new CompoundObjectType(new FormHelper($this->factory)),
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config,
            ]
        );
        $form->submit(['renamedName' => 'testName']);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('testName', $data->getName());
    }

    public function testBuildFormForFieldWithFormType()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('id'));

        $config = new EntityDefinitionConfig();
        $config->addField('id')->setFormType('integer');

        $data = new Entity\User();
        $form = $this->factory->create(
            new CompoundObjectType(new FormHelper($this->factory)),
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config,
            ]
        );
        $form->submit(['id' => '123']);
        $this->assertTrue($form->isSynchronized());
        $this->assertSame(123, $data->getId());
    }

    public function testBuildFormForFieldWithFormOptions()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('renamedName'));

        $config = new EntityDefinitionConfig();
        $config->addField('renamedName')->setFormOptions(['property_path' => 'name']);

        $data = new Entity\User();
        $form = $this->factory->create(
            new CompoundObjectType(new FormHelper($this->factory)),
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config,
            ]
        );
        $form->submit(['renamedName' => 'testName']);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('testName', $data->getName());
    }

    public function testBuildFormForIgnoredField()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('name'))
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $config = new EntityDefinitionConfig();
        $config->addField('name')->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $data = new Entity\User();
        $form = $this->factory->create(
            new CompoundObjectType(new FormHelper($this->factory)),
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config,
            ]
        );
        $form->submit(['name' => 'testName']);
        $this->assertTrue($form->isSynchronized());
        $this->assertNull($data->getName());
    }

    public function testBuildFormForFieldIgnoredOnlyForGetActions()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('name'));

        $config = new EntityDefinitionConfig();
        $fieldConfig = $config->addField('name');
        $fieldConfig->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $data = new Entity\User();
        $form = $this->factory->create(
            new CompoundObjectType(new FormHelper($this->factory)),
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config,
            ]
        );
        $form->submit(['name' => 'testName']);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('testName', $data->getName());
    }

    public function testBuildFormForAssociation()
    {
        $metadata = new EntityMetadata();
        $metadata->addAssociation(new AssociationMetadata('owner'));

        $config = new EntityDefinitionConfig();
        $config->addField('owner');

        $data = new Entity\User();
        $form = $this->factory->create(
            new CompoundObjectType(new FormHelper($this->factory)),
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config,
            ]
        );
        $form->submit(['owner' => ['name' => 'testName']]);
        $this->assertTrue($form->isSynchronized());
        $this->assertNull($data->getOwner());
    }

    public function testBuildFormForAssociationAsField()
    {
        $metadata = new EntityMetadata();
        $metadata->addAssociation(new AssociationMetadata('owner'))->setDataType('object');

        $config = new EntityDefinitionConfig();
        $field = $config->addField('owner');
        $field->setFormType('test_name_container');
        $field->setFormOptions(['data_class' => Entity\User::class]);

        $data = new Entity\User();
        $form = $this->factory->create(
            new CompoundObjectType(new FormHelper($this->factory)),
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config,
            ]
        );
        $form->submit(['owner' => ['name' => 'testName']]);
        $this->assertTrue($form->isSynchronized());
        $this->assertNotNull($data->getOwner());
        $this->assertSame('testName', $data->getOwner()->getName());
    }

    public function testCreateNestedObject()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            'form',
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            new CompoundObjectType(new FormHelper($this->factory)),
            [
                'data_class'   => Entity\ProductPrice::class,
                'by_reference' => false,
                'metadata'     => $metadata,
                'config'       => $config,
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => ['value' => 'testPriceValue']]);
        $this->assertTrue($form->isSynchronized());
        $this->assertSame('testPriceValue', $data->getPrice()->getValue());
    }

    public function testCreateNestedObjectWhenValueIsNotSubmitted()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            'form',
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            new CompoundObjectType(new FormHelper($this->factory)),
            [
                'data_class'   => Entity\ProductPrice::class,
                'by_reference' => false,
                'metadata'     => $metadata,
                'config'       => $config,
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertNull($data->getPrice()->getValue());
    }

    public function testCreateNestedObjectWhenSubmittedValueIsNull()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            'form',
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            new CompoundObjectType(new FormHelper($this->factory)),
            [
                'data_class'   => Entity\ProductPrice::class,
                'by_reference' => false,
                'metadata'     => $metadata,
                'config'       => $config,
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => null]);
        $this->assertTrue($form->isSynchronized());
        $this->assertNull($data->getPrice()->getValue());
    }

    public function testCreateNestedObjectWhenSubmittedValueIsEmptyArray()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $data = new Entity\Product();
        $formBuilder = $this->factory->createBuilder(
            'form',
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            new CompoundObjectType(new FormHelper($this->factory)),
            [
                'data_class'   => Entity\ProductPrice::class,
                'by_reference' => false,
                'metadata'     => $metadata,
                'config'       => $config,
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => []]);
        $this->assertTrue($form->isSynchronized());
        $this->assertNull($data->getPrice()->getValue());
    }

    public function testUpdateNestedObject()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $data = new Entity\Product();
        $data->setPrice(new Entity\ProductPrice('oldPriceValue', 'oldPriceCurrency'));
        $formBuilder = $this->factory->createBuilder(
            'form',
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            new CompoundObjectType(new FormHelper($this->factory)),
            [
                'data_class'   => Entity\ProductPrice::class,
                'by_reference' => false,
                'metadata'     => $metadata,
                'config'       => $config,
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => ['value' => 'newPriceValue']], false);
        $this->assertTrue($form->isSynchronized());
        $this->assertSame('newPriceValue', $data->getPrice()->getValue());
        $this->assertSame('oldPriceCurrency', $data->getPrice()->getCurrency());
    }

    public function testUpdateNestedObjectWhenValueIsNotSubmitted()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $data = new Entity\Product();
        $data->setPrice(new Entity\ProductPrice('oldPriceValue', 'oldPriceCurrency'));
        $formBuilder = $this->factory->createBuilder(
            'form',
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            new CompoundObjectType(new FormHelper($this->factory)),
            [
                'data_class'   => Entity\ProductPrice::class,
                'by_reference' => false,
                'metadata'     => $metadata,
                'config'       => $config,
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit([], false);
        $this->assertTrue($form->isSynchronized());
        $this->assertSame('oldPriceValue', $data->getPrice()->getValue());
        $this->assertSame('oldPriceCurrency', $data->getPrice()->getCurrency());
    }

    public function testUpdateNestedObjectWhenSubmittedValueIsNull()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $data = new Entity\Product();
        $data->setPrice(new Entity\ProductPrice('oldPriceValue', 'oldPriceCurrency'));
        $formBuilder = $this->factory->createBuilder(
            'form',
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            new CompoundObjectType(new FormHelper($this->factory)),
            [
                'data_class'   => Entity\ProductPrice::class,
                'by_reference' => false,
                'metadata'     => $metadata,
                'config'       => $config,
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => null], false);
        $this->assertTrue($form->isSynchronized());
        $this->assertNull($data->getPrice()->getValue());
        $this->assertNull($data->getPrice()->getCurrency());
    }

    public function testUpdateNestedObjectWhenSubmittedValueIsEmptyArray()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('value'));
        $metadata->addField(new FieldMetadata('currency'));

        $config = new EntityDefinitionConfig();
        $config->addField('value');
        $config->addField('currency');

        $data = new Entity\Product();
        $data->setPrice(new Entity\ProductPrice('oldPriceValue', 'oldPriceCurrency'));
        $formBuilder = $this->factory->createBuilder(
            'form',
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            new CompoundObjectType(new FormHelper($this->factory)),
            [
                'data_class'   => Entity\ProductPrice::class,
                'by_reference' => false,
                'metadata'     => $metadata,
                'config'       => $config,
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => []], false);
        $this->assertTrue($form->isSynchronized());
        $this->assertSame('oldPriceValue', $data->getPrice()->getValue());
        $this->assertSame('oldPriceCurrency', $data->getPrice()->getCurrency());
    }
}
