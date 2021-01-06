<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\WorkflowBundle\Datagrid\Filter\WorkflowTranslationFilter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Component\Form\FormFactoryInterface;

class WorkflowTranslationFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var WorkflowTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $translationHelper;

    /** @var FilterDatasourceAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datasourceAdapter;

    /** @var ExpressionBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $expressionBuilder;

    /** @var WorkflowTranslationFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->translationHelper = $this->createMock(WorkflowTranslationHelper::class);
        $this->datasourceAdapter = $this->createMock(FilterDatasourceAdapterInterface::class);
        $this->expressionBuilder = $this->createMock(ExpressionBuilderInterface::class);

        $this->filter = new WorkflowTranslationFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->doctrine,
            $this->translationHelper
        );
    }

    public function testInit()
    {
        $this->filter->init('test', []);

        $paramsProperty = new \ReflectionProperty($this->filter, 'params');
        $paramsProperty->setAccessible(true);
        $params = $paramsProperty->getValue($this->filter);

        $choiceLabel = $params[FilterUtility::FORM_OPTIONS_KEY]['field_options']['choice_label'];
        unset($params[FilterUtility::FORM_OPTIONS_KEY]['field_options']['choice_label']);
        self::assertEquals(
            [
                FilterUtility::FORM_OPTIONS_KEY  => [
                    'field_options' => [
                        'class'                => WorkflowDefinition::class,
                        'multiple'             => false,
                        'translatable_options' => false
                    ]
                ],
                FilterUtility::FRONTEND_TYPE_KEY => 'choice'
            ],
            $params
        );

        $definition = new WorkflowDefinition();
        $definition->setLabel('label');
        $this->translationHelper->expects(self::once())
            ->method('findTranslation')
            ->with('label')
            ->willReturn('translated-label');

        self::assertIsCallable($choiceLabel);
        self::assertEquals('translated-label', $choiceLabel($definition));
    }

    public function testApply()
    {
        $definition = (new WorkflowDefinition())->setName('definition1');

        $this->datasourceAdapter->expects(self::at(0))
            ->method('generateParameterName')
            ->with('key')
            ->willReturn('keyParameter');
        $this->datasourceAdapter->expects(self::at(1))
            ->method('generateParameterName')
            ->with('domain')
            ->willReturn('domainParameter');
        $this->datasourceAdapter->expects(self::exactly(3))
            ->method('expr')
            ->willReturn($this->expressionBuilder);

        $this->expressionBuilder->expects(self::at(0))
            ->method('eq')
            ->with('translationKey.domain', 'domainParameter', true)
            ->willReturn('expr1');
        $this->expressionBuilder->expects(self::at(1))
            ->method('like')
            ->with('translationKey.key', 'keyParameter', true)
            ->willReturn('expr2');
        $this->expressionBuilder->expects(self::at(2))
            ->method('andX')
            ->with('expr1', 'expr2')
            ->willReturn('expr3');

        $this->datasourceAdapter->expects(self::at(5))
            ->method('setParameter')
            ->with('keyParameter', 'oro.workflow.definition1%');
        $this->datasourceAdapter->expects(self::at(6))
            ->method('setParameter')
            ->with('domainParameter', 'workflows');
        $this->datasourceAdapter->expects(self::at(7))
            ->method('addRestriction')
            ->with('expr3', FilterUtility::CONDITION_AND, false);

        $this->filter->init('test', ['data_name' => 'translationKey']);
        $this->filter->apply($this->datasourceAdapter, ['value' => [$definition]]);
    }
}
