<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataMapper;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormConfigInterface;

use Oro\Bundle\ApiBundle\Form\DataMapper\AppendRelationshipMapper;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\EntityWithoutGettersAndSetters;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;

class AppendRelationshipMapperTest extends \PHPUnit_Framework_TestCase
{
    /** @var AppendRelationshipMapper */
    protected $mapper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $propertyAccessor;

    protected function setUp()
    {
        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->propertyAccessor = $this->getMock('Symfony\Component\PropertyAccess\PropertyAccessorInterface');
        $this->mapper = new AppendRelationshipMapper($this->propertyAccessor);
    }

    /**
     * @param $path
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPropertyPath($path)
    {
        return $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyPath')
            ->setConstructorArgs([$path])
            ->setMethods(['getValue', 'setValue'])
            ->getMock();
    }

    /**
     * @param FormConfigInterface $config
     * @param bool                $synchronized
     * @param bool                $submitted
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getForm(FormConfigInterface $config, $synchronized = true, $submitted = true)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->setConstructorArgs([$config])
            ->setMethods(['isSynchronized', 'isSubmitted'])
            ->getMock();

        $form->expects($this->any())
            ->method('isSynchronized')
            ->will($this->returnValue($synchronized));

        $form->expects($this->any())
            ->method('isSubmitted')
            ->will($this->returnValue($submitted));

        return $form;
    }

    public function testMapDataToFormsPassesObjectRefIfByReference()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->will($this->returnValue($engine));

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, [$form]);

        // Can't use isIdentical() above because mocks always clone their
        // arguments which can't be disabled in PHPUnit 3.6
        $this->assertSame($engine, $form->getData());
    }

    public function testMapDataToFormsPassesObjectCloneIfNotByReference()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->will($this->returnValue($engine));

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, [$form]);

        $this->assertNotSame($engine, $form->getData());
        $this->assertEquals($engine, $form->getData());
    }

    public function testMapDataToFormsIgnoresEmptyPropertyPath()
    {
        $car = new \stdClass();

        $config = new FormConfigBuilder(null, '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $form = $this->getForm($config);

        $this->assertNull($form->getPropertyPath());

        $this->mapper->mapDataToForms($car, [$form]);

        $this->assertNull($form->getData());
    }

    public function testMapDataToFormsIgnoresUnmapped()
    {
        $car = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('getValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setMapped(false);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, [$form]);

        $this->assertNull($form->getData());
    }

    public function testMapDataToFormsSetsDefaultDataIfPassedDataIsNull()
    {
        $default = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('getValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($default);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->setConstructorArgs([$config])
            ->setMethods(['setData'])
            ->getMock();

        $form->expects($this->once())
            ->method('setData')
            ->with($default);

        $this->mapper->mapDataToForms(null, [$form]);
    }

    public function testMapDataToFormsCollectionShouldBeIgnored()
    {
        $car = new \stdClass();
        $doors = new ArrayCollection([new \stdClass()]);
        $propertyPath = $this->getPropertyPath('doors');

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->will($this->returnValue($doors));

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, [$form]);

        $this->assertNull($form->getData());
    }

    public function testMapFormsToDataWritesBackIfNotByReference()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($car, $propertyPath, $engine);

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData([$form], $car);
    }

    public function testMapFormsToDataWritesBackIfByReferenceButNoReference()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($car, $propertyPath, $engine);

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData([$form], $car);
    }

    public function testMapFormsToDataWritesBackIfByReferenceAndReference()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        // $car already contains the reference of $engine
        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->will($this->returnValue($engine));

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData([$form], $car);
    }

    public function testMapFormsToDataIgnoresUnmapped()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $config->setMapped(false);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData([$form], $car);
    }

    public function testMapFormsToDataIgnoresUnsubmittedForms()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config, true, false);

        $this->mapper->mapFormsToData([$form], $car);
    }

    public function testMapFormsToDataIgnoresEmptyData()
    {
        $car = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData(null);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData([$form], $car);
    }

    public function testMapFormsToDataIgnoresUnsynchronized()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config, false);

        $this->mapper->mapFormsToData([$form], $car);
    }

    public function testMapFormsToDataIgnoresDisabled()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $config->setDisabled(true);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData([$form], $car);
    }

    public function testMapFormsToDataElementsShouldBeAddedToCollectionWhenAdderAndRemoverExist()
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
        $propertyPath = $this->getPropertyPath('groups');

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($user, $propertyPath)
            ->willReturn($user->getGroups());

        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData([$group2, $group3]);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData([$form], $user);

        $expected = new ArrayCollection();
        $expected->add($group1);
        $expected->add($group2);
        $expected->add($group3);
        $this->assertEquals($expected, $user->getGroups());
    }

    public function testMapFormsToDataElementsShouldBeAddedToCollectionWhenAdderAndRemoverDoNotExist()
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
        $propertyPath = $this->getPropertyPath('groups');

        $this->propertyAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->with($user, $propertyPath)
            ->willReturn($user->groups);

        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData([$group2, $group3]);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData([$form], $user);

        $expected = new ArrayCollection();
        $expected->add($group1);
        $expected->add($group2);
        $expected->add($group3);
        $this->assertEquals($expected, $user->groups);
    }

    public function testMapFormsToDataCollectionShouldBeReplacedWhenItDoesNotBelongsRootDataObject()
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
        $propertyPath = $this->getPropertyPath('owner.groups');

        $groupsFormData = [$group2, $group3];

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($product, $propertyPath)
            ->willReturn($user->getGroups());
        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($product, $propertyPath, $groupsFormData);

        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData($groupsFormData);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData([$form], $product);
    }
}
