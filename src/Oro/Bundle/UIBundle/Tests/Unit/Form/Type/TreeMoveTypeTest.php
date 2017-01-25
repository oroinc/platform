<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\UIBundle\Form\Type\TreeMoveType;
use Oro\Bundle\UIBundle\Model\TreeCollection;
use Oro\Bundle\UIBundle\Model\TreeItem;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class TreeMoveTypeTest extends FormIntegrationTestCase
{
    /** @var TreeMoveType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->type = new TreeMoveType();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->type);
    }

    /**
     * @dataProvider submitProvider
     *
     * @param TreeCollection $defaultData
     * @param array          $submittedData
     * @param TreeCollection $expectedData
     */
    public function testSubmit(TreeCollection $defaultData, array $submittedData, TreeCollection $expectedData)
    {
        $choices = [
            'child' => new TreeItem('child', 'Child'),
            'parent' => new TreeItem('parent', 'Parent'),
        ];

        $form = $this->factory->create($this->type, $defaultData, [
            'source_config' => [
                'choices' => $choices
            ],
            'target_config' => [
                'choices' => $choices
            ]
        ]);

        $form->submit($submittedData);

        $this->assertEquals(true, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $child = new TreeItem('child', 'Child');
        $parent = new TreeItem('parent', 'Parent');

        $collection = new TreeCollection();
        $collection->source = [$child];
        $collection->target = $parent;

        return [
            'with data' => [
                'defaultData' => new TreeCollection(),
                'submittedData' => [
                    'source' => [
                        'child'
                    ],
                    'target' => 'parent'
                ],
                'expectedData' => $collection,
            ],
            'empty data' => [
                'defaultData' => new TreeCollection(),
                'submittedData' => [],
                'expectedData' => new TreeCollection(),
            ],
        ];
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('data_class', $options);
                    $this->assertEquals(TreeCollection::class, $options['data_class']);
                    $this->assertArrayHasKey('source_config', $options);
                    $this->assertArrayHasKey('target_config', $options);
                }
            );

        $this->type->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'genemu_jqueryselect2_choice' => new Select2Type('choice'),
                ],
                ['form' => [new AdditionalAttrExtension()]]
            )
        ];
    }
}
