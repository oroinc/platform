<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ConfigurableType;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Symfony\Component\Form\FormView;

class FormTypeTest extends BlockTypeTestCase
{
    public function testConfigureOptions()
    {
        $form = $this->createMock(FormView::class);

        $this->assertEquals(
            [
                'form' => $form,
                'form_route_parameters' => [],
                'instance_name' => '',
                'additional_block_prefixes' => ['test_'],
                'visible' => true,
            ],
            $this->resolveOptions(FormType::NAME, ['form' => $form, 'additional_block_prefixes' => ['test_']])
        );
    }

    public function testBuildBlock()
    {
        $form = $this->createMock(FormView::class);

        $builder = $this->getBlockBuilder(FormType::NAME, [
            'form' => $form,
            'additional_block_prefixes' => ['test_']
        ]);

        $blockView = $this->getBlockView(FormType::NAME, [
            'form' => $form,
            'additional_block_prefixes' => ['test_']
        ]);

        $this->assertEquals($blockView, $builder->getBlockView());
        $this->assertNotNull($builder->getBlockView()->children['form_id_start']);
        $this->assertNotNull($builder->getBlockView()->children['form_id_fields']);
        $this->assertNotNull($builder->getBlockView()->children['form_id_end']);
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $formStart = new ConfigurableType();
        $formStart->setName('form_start');
        $formStart->setParent(BaseType::NAME);
        $formStart->setOptionsConfig([
            'form' => ['required' => true],
            'form_action' => null,
            'form_multipart' => null,
            'form_route_name' => null,
            'form_route_parameters' => ['default' => []],
            'instance_name' => ['default' => ''],
        ]);

        $formFields = new ConfigurableType();
        $formFields->setName('form_fields');
        $formFields->setParent(BaseType::NAME);
        $formFields->setOptionsConfig([
            'form' => ['required' => true],
            'instance_name' => ['default' => ''],
        ]);

        $formEnd = new ConfigurableType();
        $formEnd->setName('form_end');
        $formEnd->setParent(BaseType::NAME);
        $formEnd->setOptionsConfig([
            'form' => ['required' => true],
            'instance_name' => ['default' => ''],
            'render_rest' => ['default' => []],
        ]);

        $layoutFactoryBuilder
            ->addType($formStart)
            ->addType($formFields)
            ->addType($formEnd);
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(FormType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
