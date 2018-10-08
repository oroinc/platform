<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\WorkflowBundle\Datagrid\Filter\WorkflowFilter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Component\Form\FormFactoryInterface;

class WorkflowFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var WorkflowTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationHelper;

    /** @var WorkflowFilter */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->translationHelper = $this->createMock(WorkflowTranslationHelper::class);

        $this->filter = new WorkflowFilter($this->formFactory, new FilterUtility(), $this->translationHelper);
    }

    public function testInit()
    {
        $this->filter->init('test', []);

        $this->assertAttributeEquals(
            [
                FilterUtility::FORM_OPTIONS_KEY => [
                    'field_options' => [
                        'class' => WorkflowDefinition::class,
                        'multiple' => true,
                        'choice_label' => [$this->filter, 'getLabel'],
                        'translatable_options' => false
                    ],
                ],
                FilterUtility::FRONTEND_TYPE_KEY => 'choice',
            ],
            'params',
            $this->filter
        );
    }

    public function testGetLabel()
    {
        $definition = new WorkflowDefinition();
        $definition->setLabel('label');

        $this->translationHelper->expects($this->once())
            ->method('findTranslation')
            ->with('label')
            ->willReturn('translated-label');

        $this->assertEquals('translated-label', $this->filter->getLabel($definition));
    }
}
