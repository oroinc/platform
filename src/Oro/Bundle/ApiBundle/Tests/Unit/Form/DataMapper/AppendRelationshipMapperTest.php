<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataMapper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Form\DataMapper\AppendRelationshipMapper;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\EntityWithoutGettersAndSetters;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AppendRelationshipMapperTest extends TestCase
{
    private EventDispatcherInterface&MockObject $dispatcher;
    private PropertyAccessorInterface&MockObject $propertyAccessor;
    private AppendRelationshipMapper $mapper;

    #[\Override]
    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        $this->mapper = new AppendRelationshipMapper($this->propertyAccessor);
    }

    private function getForm(FormConfigInterface $config, bool $synchronized = true, bool $submitted = true): Form
    {
        $form = $this->getMockBuilder(Form::class)
            ->setConstructorArgs([$config])
            ->onlyMethods(['isSynchronized', 'isSubmitted'])
            ->getMock();

        $form->expects(self::any())
            ->method('isSynchronized')
            ->willReturn($synchronized);

        $form->expects(self::any())
            ->method('isSubmitted')
            ->willReturn($submitted);

        return $form;
    }

    public function testMapDataToFormsPassesObjectRefIfByReference(): void
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->willReturn($engine);

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, new \ArrayIterator([$form]));

        // Can't use isIdentical() above because mocks always clone their
        // arguments which can't be disabled in PHPUnit 3.6
        self::assertSame($engine, $form->getData());
    }

    public function testMapDataToFormsPassesObjectCloneIfNotByReference(): void
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->willReturn($engine);

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, new \ArrayIterator([$form]));

        self::assertNotSame($engine, $form->getData());
        self::assertEquals($engine, $form->getData());
    }

    public function testMapDataToFormsIgnoresEmptyPropertyPath(): void
    {
        $car = new \stdClass();

        $config = new FormConfigBuilder(null, \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $form = $this->getForm($config);

        self::assertNull($form->getPropertyPath());

        $this->mapper->mapDataToForms($car, new \ArrayIterator([$form]));

        self::assertNull($form->getData());
    }

    public function testMapDataToFormsIgnoresUnmapped(): void
    {
        $car = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::never())
            ->method('getValue');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setMapped(false);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, new \ArrayIterator([$form]));

        self::assertNull($form->getData());
    }

    public function testMapDataToFormsSetsDefaultDataIfPassedDataIsNull(): void
    {
        $default = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::never())
            ->method('getValue');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($default);

        $form = $this->getMockBuilder(Form::class)
            ->setConstructorArgs([$config])
            ->onlyMethods(['setData'])
            ->getMock();

        $form->expects(self::once())
            ->method('setData')
            ->with($default);

        $this->mapper->mapDataToForms(null, new \ArrayIterator([$form]));
    }

    public function testMapDataToFormsCollectionShouldBeIgnored(): void
    {
        $car = new \stdClass();
        $doors = new ArrayCollection([new \stdClass()]);
        $propertyPath = new PropertyPath('doors');

        $this->propertyAccessor->expects(self::once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->willReturn($doors);

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, new \ArrayIterator([$form]));

        self::assertNull($form->getData());
    }

    public function testMapFormsToDataWritesBackIfNotByReference(): void
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::once())
            ->method('setValue')
            ->with($car, $propertyPath, $engine);

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);
    }

    public function testMapFormsToDataWritesBackIfByReferenceButNoReference(): void
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::once())
            ->method('setValue')
            ->with($car, $propertyPath, $engine);

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);
    }

    public function testMapFormsToDataWritesBackIfByReferenceAndReference(): void
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        // $car already contains the reference of $engine
        $this->propertyAccessor->expects(self::once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->willReturn($engine);

        $this->propertyAccessor->expects(self::never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);
    }

    public function testMapFormsToDataIgnoresUnmapped(): void
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $config->setMapped(false);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);
    }

    public function testMapFormsToDataIgnoresUnsubmittedForms(): void
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config, true, false);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);
    }

    public function testMapFormsToDataIgnoresEmptyData(): void
    {
        $car = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData(null);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);
    }

    public function testMapFormsToDataIgnoresUnsynchronized(): void
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config, false);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);
    }

    public function testMapFormsToDataIgnoresDisabled(): void
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $this->propertyAccessor->expects(self::never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $config->setDisabled(true);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);
    }

    public function testMapFormsToDataElementsShouldBeAddedToCollectionWhenAdderAndRemoverExist(): void
    {
        $group1 = new Group();
        $group1->setName('group1');
        $group2 = new Group();
        $group2->setName('group2');
        $group3 = new Group();
        $group3->setName('group3');
        $user = new User();
        $user->addGroup($group1);
        $user->addGroup($group2);
        $propertyPath = new PropertyPath('groups');

        $this->propertyAccessor->expects(self::once())
            ->method('getValue')
            ->with($user, $propertyPath)
            ->willReturn($user->getGroups());

        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData([$group2, $group3]);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $user);

        $expected = new ArrayCollection();
        $expected->add($group1);
        $expected->add($group2);
        $expected->add($group3);
        self::assertEquals($expected, $user->getGroups());
    }

    public function testMapFormsToDataElementsShouldBeAddedToCollectionWhenAdderAndRemoverDoNotExist(): void
    {
        $group1 = new Group();
        $group1->setName('group1');
        $group2 = new Group();
        $group2->setName('group2');
        $group3 = new Group();
        $group3->setName('group3');
        $user = new EntityWithoutGettersAndSetters();
        $user->groups->add($group1);
        $user->groups->add($group2);
        $propertyPath = new PropertyPath('groups');

        $this->propertyAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->with($user, $propertyPath)
            ->willReturn($user->groups);

        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData([$group2, $group3]);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $user);

        $expected = new ArrayCollection();
        $expected->add($group1);
        $expected->add($group2);
        $expected->add($group3);
        self::assertEquals($expected, $user->groups);
    }

    public function testMapFormsToDataCollectionShouldBeReplacedWhenItDoesNotBelongsRootDataObject(): void
    {
        $group1 = new Group();
        $group1->setName('group1');
        $group2 = new Group();
        $group2->setName('group2');
        $group3 = new Group();
        $group3->setName('group3');
        $user = new User();
        $user->addGroup($group1);
        $user->addGroup($group2);
        $product = new Product();
        $product->setOwner($user);
        $propertyPath = new PropertyPath('owner.groups');

        $groupsFormData = [$group2, $group3];

        $this->propertyAccessor->expects(self::once())
            ->method('getValue')
            ->with($product, $propertyPath)
            ->willReturn($user->getGroups());
        $this->propertyAccessor->expects(self::once())
            ->method('setValue')
            ->with($product, $propertyPath, $groupsFormData);

        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData($groupsFormData);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $product);
    }

    public function testMapFormsToDataElementsShouldBeAddedToCollectionInCaseOfExtendedToManyAssociation(): void
    {
        $group1 = new Group();
        $group1->setName('group1');
        $group2 = new Group();
        $group2->setName('group2');
        $group3 = new Group();
        $group3->setName('group3');
        $user = new User();
        $product = new Product();
        $product->setName('test product');

        $user->addGroup($group1);
        $user->addGroup($group2);
        $propertyPath = new PropertyPath('targets');

        $this->propertyAccessor->expects(self::any())
            ->method('getValue')
            ->with($user, $propertyPath)
            ->willReturn($user->getTargets());

        $metadata = new AssociationMetadata();
        $metadata->setIsCollection(true);

        $config = new FormConfigBuilder('targets', null, $this->dispatcher, ['metadata' => $metadata]);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData([$group2, $group3, $product]);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $user);

        $expectedGroups = new ArrayCollection();
        $expectedGroups->add($group1);
        $expectedGroups->add($group2);
        $expectedGroups->add($group3);
        self::assertEquals($expectedGroups, $user->getGroups());

        $expectedProducts = new ArrayCollection();
        $expectedProducts->add($product);
        self::assertEquals($expectedProducts, $user->getProducts());
    }
}
