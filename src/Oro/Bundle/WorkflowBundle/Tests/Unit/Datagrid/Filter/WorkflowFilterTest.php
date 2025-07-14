<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\WorkflowBundle\Datagrid\Filter\WorkflowFilter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

class WorkflowFilterTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private ManagerRegistry&MockObject $doctrine;
    private WorkflowTranslationHelper&MockObject $translationHelper;
    private WorkflowFilter $filter;

    #[\Override]
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

    public function testInit(): void
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
