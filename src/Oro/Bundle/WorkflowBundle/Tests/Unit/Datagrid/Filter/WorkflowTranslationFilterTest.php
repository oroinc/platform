<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\WorkflowBundle\Datagrid\Filter\WorkflowTranslationFilter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactoryInterface;

class WorkflowTranslationFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|MockObject */
    protected $formFactory;

    /** @var WorkflowTranslationHelper|MockObject */
    protected $translationHelper;

    /** @var FilterDatasourceAdapterInterface|MockObject */
    protected $datasourceAdapter;

    /** @var ExpressionBuilderInterface|MockObject */
    protected $expressionBuilder;

    /** @var WorkflowTranslationFilter */
    protected $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->translationHelper = $this->createMock(WorkflowTranslationHelper::class);
        $this->datasourceAdapter = $this->createMock(FilterDatasourceAdapterInterface::class);
        $this->expressionBuilder = $this->createMock(ExpressionBuilderInterface::class);

        $this->filter = new class(
            $this->formFactory,
            new FilterUtility(),
            $this->translationHelper
        ) extends WorkflowTranslationFilter {
            public function xgetParams(): array
            {
                return $this->params;
            }
        };
    }

    protected function tearDown(): void
    {
        unset($this->formFactory, $this->generator, $this->translationHelper, $this->filter);
    }

    public function testInit()
    {
        $this->filter->init('test', []);
        static::assertEquals(
            [
                FilterUtility::FORM_OPTIONS_KEY => [
                    'field_options' => [
                        'class' => WorkflowDefinition::class,
                        'multiple' => false,
                        'choice_label' => [$this->filter, 'getLabel'],
                        'translatable_options' => false
                    ],
                ],
                FilterUtility::FRONTEND_TYPE_KEY => 'choice',
            ],
            $this->filter->xgetParams()
        );
    }

    public function testApply()
    {
        $definition = (new WorkflowDefinition())->setName('definition1');

        $this->datasourceAdapter->expects(static::at(0))->method('generateParameterName')
            ->with('key')
            ->willReturn('keyParameter');

        $this->datasourceAdapter->expects(static::at(1))->method('generateParameterName')
            ->with('domain')
            ->willReturn('domainParameter');

        $this->datasourceAdapter->expects(static::exactly(3))->method('expr')->willReturn($this->expressionBuilder);

        $this->expressionBuilder->expects(static::at(0))->method('eq')
            ->with('translationKey.domain', 'domainParameter', true)
            ->willReturn('expr1');

        $this->expressionBuilder->expects(static::at(1))->method('like')
            ->with('translationKey.key', 'keyParameter', true)
            ->willReturn('expr2');

        $this->expressionBuilder->expects(static::at(2))->method('andX')
            ->with('expr1', 'expr2')
            ->willReturn('expr3');

        $this->datasourceAdapter->expects(static::at(5))
            ->method('setParameter')
            ->with('keyParameter', 'oro.workflow.definition1%');

        $this->datasourceAdapter->expects(static::at(6))
            ->method('setParameter')
            ->with('domainParameter', 'workflows');

        $this->datasourceAdapter->expects(static::at(7))
            ->method('addRestriction')
            ->with('expr3', FilterUtility::CONDITION_AND, false);

        $this->filter->init('test', ['data_name' => 'translationKey']);
        $this->filter->apply($this->datasourceAdapter, ['value' => [$definition]]);
    }
}
