<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\WorkflowBundle\Datagrid\Filter\WorkflowTranslationFilter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormFactoryInterface;

class WorkflowTranslationFilterTest extends \PHPUnit\Framework\TestCase
{
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
        $this->translationHelper = $this->createMock(WorkflowTranslationHelper::class);
        $this->datasourceAdapter = $this->createMock(FilterDatasourceAdapterInterface::class);
        $this->expressionBuilder = $this->createMock(ExpressionBuilderInterface::class);

        $this->filter = new WorkflowTranslationFilter(
            $this->createMock(FormFactoryInterface::class),
            new FilterUtility(),
            $this->createMock(ManagerRegistry::class),
            $this->translationHelper
        );
    }

    public function testInit()
    {
        $this->filter->init('test', []);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        $choiceLabel = $params[FilterUtility::FORM_OPTIONS_KEY]['field_options']['choice_label'];
        unset($params[FilterUtility::FORM_OPTIONS_KEY]['field_options']['choice_label']);
        self::assertEquals(
            [
                FilterUtility::FORM_OPTIONS_KEY  => [
                    'field_options' => [
                        'class'    => WorkflowDefinition::class,
                        'multiple' => false
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

        $this->datasourceAdapter->expects(self::exactly(2))
            ->method('generateParameterName')
            ->withConsecutive(['key'], ['domain'])
            ->willReturnOnConsecutiveCalls('keyParameter', 'domainParameter');
        $this->datasourceAdapter->expects(self::exactly(3))
            ->method('expr')
            ->willReturn($this->expressionBuilder);

        $this->expressionBuilder->expects(self::once())
            ->method('eq')
            ->with('translationKey.domain', 'domainParameter', true)
            ->willReturn('expr1');
        $this->expressionBuilder->expects(self::once())
            ->method('like')
            ->with('translationKey.key', 'keyParameter', true)
            ->willReturn('expr2');
        $this->expressionBuilder->expects(self::once())
            ->method('andX')
            ->with('expr1', 'expr2')
            ->willReturn('expr3');

        $this->datasourceAdapter->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['keyParameter', 'oro.workflow.definition1%'],
                ['domainParameter', 'workflows']
            );
        $this->datasourceAdapter->expects(self::once())
            ->method('addRestriction')
            ->with('expr3', FilterUtility::CONDITION_AND, false);

        $this->filter->init('test', ['data_name' => 'translationKey']);
        $this->filter->apply($this->datasourceAdapter, ['value' => [$definition]]);
    }
}
