<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

use Oro\Bundle\ApiBundle\Form\Type\EntityType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class EntityTypeTest extends OrmRelatedTestCase
{
    /** @var FormFactoryInterface */
    protected $factory;

    protected function setUp()
    {
        parent::setUp();

        $this->factory = Forms::createFormFactoryBuilder()->getFormFactory();
    }

    /**
     * @dataProvider validEmptyValuesDataProvider
     */
    public function testWithValidEmptyValue($value, $expected)
    {
        $form = $this->factory->create(new EntityType($this->doctrine));
        $form->submit($value);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }

    public function validEmptyValuesDataProvider()
    {
        return [
            [null, null],
            ['', null],
            [[], null],
        ];
    }

    /**
     * @dataProvider validMultipleEmptyValuesDataProvider
     */
    public function testMultipleWithValidEmptyValue($value, $expected)
    {
        $form = $this->factory->create(new EntityType($this->doctrine), null, ['multiple' => true]);
        $form->submit($value);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }

    public function validMultipleEmptyValuesDataProvider()
    {
        return [
            [null, new ArrayCollection()],
            ['', new ArrayCollection()],
            [[], new ArrayCollection()],
        ];
    }

    public function testWithValidValue()
    {
        $value = ['class' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group', 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $stmt = $this->createFetchStatementMock(
            [
                [
                    'id_1'   => $entity->getId(),
                    'name_2' => $entity->getName()
                ]
            ],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->with('SELECT t0.id AS id_1, t0.name AS name_2 FROM group_table t0 WHERE t0.id = ?')
            ->willReturn($stmt);

        $form = $this->factory->create(new EntityType($this->doctrine));
        $form->submit($value);
        $this->assertTrue($form->isSynchronized());
    }

    public function testMultipleWithValidValue()
    {
        $value = ['class' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group', 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $stmt = $this->createFetchStatementMock(
            [
                [
                    'id_1'   => $entity->getId(),
                    'name_2' => $entity->getName()
                ]
            ],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->with('SELECT t0.id AS id_1, t0.name AS name_2 FROM group_table t0 WHERE t0.id = ?')
            ->willReturn($stmt);

        $form = $this->factory->create(new EntityType($this->doctrine), null, ['multiple' => true]);
        $form->submit([$value]);
        $this->assertTrue($form->isSynchronized());
    }

    public function testWithInvalidValue()
    {
        $form = $this->factory->create(new EntityType($this->doctrine));
        $form->submit('test');
        $this->assertFalse($form->isSynchronized());
    }

    public function testMultipleWithInvalidValue()
    {
        $form = $this->factory->create(new EntityType($this->doctrine), null, ['multiple' => true]);
        $form->submit('test');
        $this->assertFalse($form->isSynchronized());
    }

    public function testGetName()
    {
        $type = new EntityType($this->doctrine);
        $this->assertEquals('oro_api_entity', $type->getName());
    }
}
