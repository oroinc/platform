<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Filter\WorkflowFilter;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class WorkflowFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var WorkflowTranslationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationHelper;

    /** @var FilterDatasourceAdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $datasourceAdapter;

    /** @var ExpressionBuilderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $expressionBuilder;

    /** @var WorkflowFilter */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formFactory = $this->getMock(FormFactoryInterface::class);
        $this->translationHelper = $this->getMockBuilder(WorkflowTranslationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->datasourceAdapter = $this->getMock(FilterDatasourceAdapterInterface::class);
        $this->expressionBuilder = $this->getMock(ExpressionBuilderInterface::class);

        $this->filter = new WorkflowFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->translationHelper
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formFactory, $this->generator, $this->translationHelper, $this->filter);
    }

    public function testInit()
    {
        $this->filter->init('test', []);
        $this->assertAttributeEquals(
            [
                FilterUtility::FORM_OPTIONS_KEY => [
                    'field_options' => [
                        'class' => WorkflowDefinition::class,
                        'multiple' => false,
                        'choice_label' => [$this->filter, 'getLabel'],
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
        $definition = (new WorkflowDefinition())->setLabel('label');

        $this->translationHelper->expects($this->once())
            ->method('findTranslation')
            ->with('label')
            ->willReturn('translated-label');

        $this->assertEquals('translated-label', $this->filter->getLabel($definition));
    }

    public function testApply()
    {
        $definition = (new WorkflowDefinition())->setName('definition1');

        $this->datasourceAdapter->expects($this->at(0))->method('generateParameterName')
            ->with('key')
            ->willReturn('keyParameter');

        $this->datasourceAdapter->expects($this->at(1))->method('generateParameterName')
            ->with('domain')
            ->willReturn('domainParameter');

        $this->datasourceAdapter->expects($this->exactly(3))->method('expr')->willReturn($this->expressionBuilder);

        $this->expressionBuilder->expects($this->at(0))->method('eq')
            ->with('translationKey.domain', 'domainParameter', true)
            ->willReturn('expr1');

        $this->expressionBuilder->expects($this->at(1))->method('like')
            ->with('translationKey.key', 'keyParameter', true)
            ->willReturn('expr2');

        $this->expressionBuilder->expects($this->at(2))->method('andX')
            ->with('expr1', 'expr2')
            ->willReturn('expr3');

        $this->datasourceAdapter->expects($this->at(5))
            ->method('setParameter')
            ->with('keyParameter', 'oro.workflow.definition1%');

        $this->datasourceAdapter->expects($this->at(6))
            ->method('setParameter')
            ->with('domainParameter', 'workflows');

        $this->datasourceAdapter->expects($this->at(7))
            ->method('addRestriction')
            ->with('expr3', FilterUtility::CONDITION_AND, false);

        $this->filter->init('test', ['data_name' => 'translationKey']);
        $this->filter->apply($this->datasourceAdapter, ['value' => [$definition]]);
    }
}
