<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\WorkflowBundle\Datagrid\Filter\WorkflowFilter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormFactoryInterface;

class WorkflowFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var WorkflowTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $translationHelper;

    /** @var WorkflowFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->translationHelper = $this->createMock(WorkflowTranslationHelper::class);

        $this->filter = new WorkflowFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->doctrine,
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
                        'multiple' => true
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
}
