<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\ApiBundle\Form\Type\CollectionType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;

class CollectionTypeTest extends TypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension(
                ['collection_entry' => new CollectionEntryType()],
                []
            )
        ];
    }

    public function testShouldUseAdder()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $entity */
        $entity = $this->getMockBuilder(User::class)
            ->setMethods(['addGroup', 'removeGroup'])
            ->getMock();

        $group1 = new Group();
        $group1->setId(1);
        $group1->setName('group1');
        $group2 = new Group();
        $group2->setName('group2');

        $entity->getGroups()->add($group1);

        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $entity,
            ['data_class' => User::class]
        );
        $formBuilder->add(
            'groups',
            new CollectionType(),
            [
                'entry_data_class' => Group::class,
                'entry_type'       => 'collection_entry'
            ]
        );
        $form = $formBuilder->getForm();

        $entity->expects($this->once())
            ->method('addGroup')
            ->with($group2);
        $entity->expects($this->never())
            ->method('removeGroup');

        $form->submit(['groups' => [['name' => 'group1'], ['name' => 'group2']]]);
        $this->assertTrue($form->isSynchronized());
    }

    public function testShouldUseRemover()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $entity */
        $entity = $this->getMockBuilder(User::class)
            ->setMethods(['addGroup', 'removeGroup'])
            ->getMock();

        $group1 = new Group();
        $group1->setId(1);
        $group1->setName('group1');
        $group2 = new Group();
        $group2->setId(2);
        $group2->setName('group2');

        $entity->getGroups()->add($group1);
        $entity->getGroups()->add($group2);

        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $entity,
            ['data_class' => User::class]
        );
        $formBuilder->add(
            'groups',
            new CollectionType(),
            [
                'entry_data_class' => Group::class,
                'entry_type'       => 'collection_entry'
            ]
        );
        $form = $formBuilder->getForm();

        $entity->expects($this->never())
            ->method('addGroup');
        $entity->expects($this->once())
            ->method('removeGroup')
            ->with($this->identicalTo($group2));

        $form->submit(['groups' => [['name' => 'group1']]]);
        $this->assertTrue($form->isSynchronized());
    }

    public function testShouldUpdateExistingEntity()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $entity */
        $entity = $this->getMockBuilder(User::class)
            ->setMethods(['addGroup', 'removeGroup'])
            ->getMock();

        $group1 = new Group();
        $group1->setId(1);
        $group1->setName('group1');

        $entity->getGroups()->add($group1);

        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $entity,
            ['data_class' => User::class]
        );
        $formBuilder->add(
            'groups',
            new CollectionType(),
            [
                'entry_data_class' => Group::class,
                'entry_type'       => 'collection_entry'
            ]
        );
        $form = $formBuilder->getForm();

        $entity->expects($this->never())
            ->method('addGroup');
        $entity->expects($this->never())
            ->method('removeGroup');

        $form->submit(['groups' => [['name' => 'group2']]]);
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals('group2', $group1->getName());
    }

    public function testShouldUseRemoverWhenRemoveAllItems()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $entity */
        $entity = $this->getMockBuilder(User::class)
            ->setMethods(['addGroup', 'removeGroup'])
            ->getMock();

        $group1 = new Group();
        $group1->setId(1);
        $group1->setName('group1');

        $entity->getGroups()->add($group1);

        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $entity,
            ['data_class' => User::class]
        );
        $formBuilder->add(
            'groups',
            new CollectionType(),
            [
                'entry_data_class' => Group::class,
                'entry_type'       => 'collection_entry'
            ]
        );
        $form = $formBuilder->getForm();

        $entity->expects($this->never())
            ->method('addGroup');
        $entity->expects($this->once())
            ->method('removeGroup')
            ->with($this->identicalTo($group1));

        $form->submit(['groups' => []]);
        $this->assertTrue($form->isSynchronized());
    }

    public function testShouldValidateEntryEntity()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $entity */
        $entity = $this->getMockBuilder(User::class)
            ->setMethods(['addGroup', 'removeGroup'])
            ->getMock();

        $group1 = new Group();
        $group1->setId(1);
        $group1->setName('group1');

        $entity->getGroups()->add($group1);

        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $entity,
            ['data_class' => User::class]
        );
        $formBuilder->add(
            'groups',
            new CollectionType(),
            [
                'entry_data_class' => Group::class,
                'entry_type'       => 'collection_entry'
            ]
        );
        $form = $formBuilder->getForm();

        $entity->expects($this->never())
            ->method('addGroup');
        $entity->expects($this->never())
            ->method('removeGroup');

        $form->submit(['groups' => ['']]);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(0, $form->getErrors());
        $this->assertCount(0, $form->get('groups')->getErrors());
        $this->assertCount(1, $form->get('groups')->get(0)->getErrors());
    }

    public function testWithInvalidValue()
    {
        $form = $this->factory->create(
            new CollectionType(),
            null,
            [
                'entry_data_class' => Group::class,
                'entry_type'       => 'collection_entry'
            ]
        );
        $form->submit('test');
        $this->assertFalse($form->isSynchronized());
    }

    public function testGetName()
    {
        $type = new CollectionType();
        $this->assertEquals('oro_api_collection', $type->getName());
    }
}
