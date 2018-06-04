<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Type\CompoundObjectType;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\NameContainerType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class CompoundObjectTypeTest extends TypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [new CompoundObjectType($this->getFormHelper())],
                []
            )
        ];
    }

    /**
     * @return FormHelper
     */
    protected function getFormHelper()
    {
        return new FormHelper(
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(ContainerInterface::class)
        );
    }

    public function testBuildFormForField()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('name'));

        $config = new EntityDefinitionConfig();
        $config->addField('name');

        $data = new Entity\User();
        $form = $this->factory->create(
            CompoundObjectType::class,
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form->submit(['name' => 'testName']);
        self::assertTrue($form->isSynchronized());
        self::assertEquals('testName', $data->getName());
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
            CompoundObjectType::class,
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form->submit(['renamedName' => 'testName']);
        self::assertTrue($form->isSynchronized());
        self::assertEquals('testName', $data->getName());
    }

    public function testBuildFormForFieldWithFormType()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('id'));

        $config = new EntityDefinitionConfig();
        $config->addField('id')->setFormType(IntegerType::class);

        $data = new Entity\User();
        $form = $this->factory->create(
            CompoundObjectType::class,
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form->submit(['id' => '123']);
        self::assertTrue($form->isSynchronized());
        self::assertSame(123, $data->getId());
    }

    public function testBuildFormForFieldWithFormOptions()
    {
        $metadata = new EntityMetadata();
        $metadata->addField(new FieldMetadata('renamedName'));

        $config = new EntityDefinitionConfig();
        $config->addField('renamedName')->setFormOptions(['property_path' => 'name']);

        $data = new Entity\User();
        $form = $this->factory->create(
            CompoundObjectType::class,
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form->submit(['renamedName' => 'testName']);
        self::assertTrue($form->isSynchronized());
        self::assertEquals('testName', $data->getName());
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
            CompoundObjectType::class,
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form->submit(['name' => 'testName']);
        self::assertTrue($form->isSynchronized());
        self::assertNull($data->getName());
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
            CompoundObjectType::class,
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form->submit(['name' => 'testName']);
        self::assertTrue($form->isSynchronized());
        self::assertEquals('testName', $data->getName());
    }

    public function testBuildFormForAssociation()
    {
        $metadata = new EntityMetadata();
        $metadata->addAssociation(new AssociationMetadata('owner'));

        $config = new EntityDefinitionConfig();
        $config->addField('owner');

        $data = new Entity\User();
        $form = $this->factory->create(
            CompoundObjectType::class,
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form->submit(['owner' => ['name' => 'testName']]);
        self::assertTrue($form->isSynchronized());
        self::assertNull($data->getOwner());
    }

    public function testBuildFormForAssociationAsField()
    {
        $metadata = new EntityMetadata();
        $metadata->addAssociation(new AssociationMetadata('owner'))->setDataType('object');

        $config = new EntityDefinitionConfig();
        $field = $config->addField('owner');
        $field->setFormType(NameContainerType::class);
        $field->setFormOptions(['data_class' => Entity\User::class]);

        $data = new Entity\User();
        $form = $this->factory->create(
            CompoundObjectType::class,
            $data,
            [
                'data_class' => Entity\User::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form->submit(['owner' => ['name' => 'testName']]);
        self::assertTrue($form->isSynchronized());
        self::assertNotNull($data->getOwner());
        self::assertSame('testName', $data->getOwner()->getName());
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
            FormType::class,
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            CompoundObjectType::class,
            [
                'data_class' => Entity\ProductPrice::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => ['value' => 'testPriceValue']]);
        self::assertTrue($form->isSynchronized());
        self::assertSame('testPriceValue', $data->getPrice()->getValue());
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
            FormType::class,
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            CompoundObjectType::class,
            [
                'data_class' => Entity\ProductPrice::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit([]);
        self::assertTrue($form->isSynchronized());
        self::assertNull($data->getPrice()->getValue());
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
            FormType::class,
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            CompoundObjectType::class,
            [
                'data_class' => Entity\ProductPrice::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => null]);
        self::assertTrue($form->isSynchronized());
        self::assertNull($data->getPrice()->getValue());
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
            FormType::class,
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            CompoundObjectType::class,
            [
                'data_class' => Entity\ProductPrice::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => []]);
        self::assertTrue($form->isSynchronized());
        self::assertNull($data->getPrice()->getValue());
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
            FormType::class,
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            CompoundObjectType::class,
            [
                'data_class' => Entity\ProductPrice::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => ['value' => 'newPriceValue']], false);
        self::assertTrue($form->isSynchronized());
        self::assertSame('newPriceValue', $data->getPrice()->getValue());
        self::assertSame('oldPriceCurrency', $data->getPrice()->getCurrency());
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
            FormType::class,
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            CompoundObjectType::class,
            [
                'data_class' => Entity\ProductPrice::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit([], false);
        self::assertTrue($form->isSynchronized());
        self::assertSame('oldPriceValue', $data->getPrice()->getValue());
        self::assertSame('oldPriceCurrency', $data->getPrice()->getCurrency());
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
            FormType::class,
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            CompoundObjectType::class,
            [
                'data_class' => Entity\ProductPrice::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => null], false);
        self::assertTrue($form->isSynchronized());
        self::assertNull($data->getPrice()->getValue());
        self::assertNull($data->getPrice()->getCurrency());
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
            FormType::class,
            $data,
            ['data_class' => Entity\Product::class]
        );
        $formBuilder->add(
            'price',
            CompoundObjectType::class,
            [
                'data_class' => Entity\ProductPrice::class,
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );
        $form = $formBuilder->getForm();
        $form->submit(['price' => []], false);
        self::assertTrue($form->isSynchronized());
        self::assertSame('oldPriceValue', $data->getPrice()->getValue());
        self::assertSame('oldPriceCurrency', $data->getPrice()->getCurrency());
    }
}
