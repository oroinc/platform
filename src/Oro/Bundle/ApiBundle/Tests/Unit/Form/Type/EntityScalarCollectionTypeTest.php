<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\ApiBundle\Form\Type\EntityScalarCollectionType;
use Oro\Bundle\ApiBundle\Form\Type\ScalarCollectionType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;

class EntityScalarCollectionTypeTest extends TypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension(
                ['oro_api_scalar_collection' => new ScalarCollectionType()],
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
            new EntityScalarCollectionType(),
            [
                'entry_data_class'    => Group::class,
                'entry_data_property' => 'name'
            ]
        );
        $form = $formBuilder->getForm();

        $form->submit(['groups' => []]);
        $this->assertTrue($form->isSynchronized());
    }

    public function testGetName()
    {
        $type = new EntityScalarCollectionType();
        $this->assertEquals('oro_api_entity_scalar_collection', $type->getName());
    }

    public function testGetParent()
    {
        $type = new EntityScalarCollectionType();
        $this->assertEquals('oro_api_scalar_collection', $type->getParent());
    }
}
