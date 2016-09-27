<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\ApiBundle\Form\Type\EntityCollectionType;
use Oro\Bundle\ApiBundle\Form\Type\CollectionType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;

class EntityCollectionTypeTest extends TypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension(
                [
                    'oro_api_collection' => new CollectionType(),
                    'collection_entry'   => new CollectionEntryType()
                ],
                []
            )
        ];
    }

    public function testShouldClearCollectionWhenRemoveAllItems()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ArrayCollection $groups */
        $groups = $this->getMockBuilder(ArrayCollection::class)
            ->setMethods(['clear'])
            ->getMock();

        $groups->expects($this->once())
            ->method('clear');

        $entity = new User();
        $entity->setGroups($groups);

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
            new EntityCollectionType(),
            [
                'entry_data_class' => Group::class,
                'entry_type'       => 'collection_entry'
            ]
        );
        $form = $formBuilder->getForm();

        $form->submit(['groups' => []]);
        $this->assertTrue($form->isSynchronized());
    }

    public function testGetName()
    {
        $type = new EntityCollectionType();
        $this->assertEquals('oro_api_entity_collection', $type->getName());
    }

    public function testGetParent()
    {
        $type = new EntityCollectionType();
        $this->assertEquals('oro_api_collection', $type->getParent());
    }
}
