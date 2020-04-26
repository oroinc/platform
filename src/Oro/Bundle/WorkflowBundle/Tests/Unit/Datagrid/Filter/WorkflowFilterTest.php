<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\WorkflowBundle\Datagrid\Filter\WorkflowFilter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactoryInterface;

class WorkflowFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|MockObject */
    protected $formFactory;

    /** @var WorkflowTranslationHelper|MockObject */
    protected $translationHelper;

    /** @var WorkflowFilter */
    protected $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->translationHelper = $this->createMock(WorkflowTranslationHelper::class);

        $this->filter = new class(
            $this->formFactory,
            new FilterUtility(),
            $this->translationHelper
        ) extends WorkflowFilter {
            public function xgetParams(): array
            {
                return $this->params;
            }
        };
    }

    public function testInit()
    {
        $this->filter->init('test', []);

        static::assertEquals(
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
            $this->filter->xgetParams()
        );
    }

    public function testGetLabel()
    {
        $definition = new WorkflowDefinition();
        $definition->setLabel('label');

        $this->translationHelper->expects(static::once())
            ->method('findTranslation')
            ->with('label')
            ->willReturn('translated-label');

        static::assertEquals('translated-label', $this->filter->getLabel($definition));
    }
}
