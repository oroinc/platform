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
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityChangesetType */
    private $type;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->type = new EntityChangesetType($this->doctrineHelper);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                $this->type,
                new DataChangesetType()
            ], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(DataChangesetType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['class']);
        $this->type->configureOptions($resolver);
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ArrayCollection $defaultData,
        string $viewData,
        string $submittedData,
        ArrayCollection $expected
    ) {
        $this->doctrineHelper->expects($expected->isEmpty() ? $this->never() : $this->exactly($expected->count()))
            ->method('getEntityReference')
            ->willReturnCallback(function () {
                return $this->createDataObject(func_get_arg(1));
            });

        $form = $this->factory->create(EntityChangesetType::class, $defaultData, ['class' => \stdClass::class]);

        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();

        $this->assertEquals($expected, $data);
    }

    public function submitProvider(): array
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

    private function createDataObject(int $id): \stdClass
    {
        $obj = new \stdClass();
        $obj->id = $id;

        return $obj;
    }
}
