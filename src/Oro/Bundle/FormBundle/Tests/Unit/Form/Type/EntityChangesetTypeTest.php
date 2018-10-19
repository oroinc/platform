<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityChangesetTypeTest extends FormIntegrationTestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var EntityChangesetType
     */
    protected $type;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new EntityChangesetType($this->doctrineHelper);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([
                EntityChangesetType::class => $this->type,
                DataChangesetType::class => new DataChangesetType()
            ], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(DataChangesetType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['class']);
        $this->type->configureOptions($resolver);
    }

    /**
     * @dataProvider submitProvider
     *
     * @param $defaultData
     * @param $viewData
     * @param $submittedData
     * @param ArrayCollection $expected
     */
    public function testSubmit($defaultData, $viewData, $submittedData, ArrayCollection $expected)
    {
        $this->doctrineHelper->expects($expected->isEmpty() ? $this->never() : $this->exactly($expected->count()))
            ->method('getEntityReference')
            ->will(
                $this->returnCallback(
                    function () {
                        return $this->createDataObject(func_get_arg(1));
                    }
                )
            );

        $form = $this->factory->create(EntityChangesetType::class, $defaultData, ['class' => '\stdClass']);

        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $data = $form->getData();

        $this->assertEquals($expected, $data);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            [
                'defaultData' => new ArrayCollection([
                    '1' => ['entity' => $this->createDataObject(1), 'data' => ['test' => '123', 'test2' => 'val']],
                    '2' => ['entity' => $this->createDataObject(2), 'data' => ['test' => '12']]
                ]),
                'viewData' =>  json_encode([
                    '1' => ['test' => '123', 'test2' => 'val'],
                    '2' => ['test' => '12']
                ]),
                'submittedData'  => json_encode([
                    '1' => ['test' => '321', 'test2' => 'val'],
                    '2' => ['test' => '21']
                ]),
                'expected' => new ArrayCollection([
                    '1' => ['entity' => $this->createDataObject(1), 'data' => ['test' => '321', 'test2' => 'val']],
                    '2' => ['entity' => $this->createDataObject(2), 'data' => ['test' => '21']]
                ]),
            ]
        ];
    }

    /**
     * @param int $id
     * @return \stdClass
     */
    protected function createDataObject($id)
    {
        $obj = new \stdClass();
        $obj->id = $id;

        return $obj;
    }
}
