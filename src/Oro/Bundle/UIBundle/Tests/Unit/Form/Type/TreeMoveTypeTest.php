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
        $treeItems = [
            'child' => new TreeItem('child', 'Child'),
            'parent' => new TreeItem('parent', 'Parent'),
        ];
        $treeItems['child']->setParent($treeItems['parent']);

        $treeData = [
            ['id' => 'child', 'text' => 'Child'],
            ['id' => 'parent', 'text' => 'Parent'],
        ];

        $form = $this->factory->create($this->type, $defaultData, [
            'tree_items' => $treeItems,
            'tree_data' => $treeData,
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
        $parent = new TreeItem('parent', 'Parent');

        $child = new TreeItem('child', 'Child');
        $child->setParent($parent);

        $collection = new TreeCollection();
        $collection->target = $parent;

        return [
            'with data' => [
                'defaultData' => new TreeCollection(),
                'submittedData' => [
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

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([], ['form' => [new AdditionalAttrExtension()]])
        ];
    }
}
