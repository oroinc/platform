<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Form\Extension\RestrictionsExtension;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;

class RestrictionsExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowManager;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var RestrictionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $restrictionsManager;

    /**
     * @var RestrictionsExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->restrictionsManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflowManager     = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper      = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new RestrictionsExtension(
            $this->workflowManager,
            $this->doctrineHelper,
            $this->restrictionsManager
        );
        parent::setUp();
    }

    /**
     * @dataProvider finishViewWithDisabledExtensionProvider
     */
    public function testFinishViewWithDisabledExtension(FormInterface $form, array $options, $hasRestrictions)
    {
        $this->restrictionsManager
            ->expects($this->any())
            ->method('hasEntityClassRestrictions')
            ->with('Test')
            ->will($this->returnValue($hasRestrictions));

        $this->restrictionsManager
            ->expects($this->never())
            ->method('getEntityRestrictions');

        $this->extension->finishView(new FormView(), $form, $options);
    }

    public function finishViewWithDisabledExtensionProvider()
    {
        $formWithoutData = $this->getMock('Symfony\Component\Form\FormInterface');
        $formWithoutData
            ->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(null));

        $formWithData = $this->getMock('Symfony\Component\Form\FormInterface');
        $formWithData
            ->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(['status' => 'something']));

        return [
            [
                $formWithData,
                [
                    'disable_workflow_restrictions' => false,
                ],
                false,
            ],
            [
                $formWithData,
                [
                    'disable_workflow_restrictions' => false,
                    'data_class' => ''
                ],
                false,
            ],
            [
                $formWithData,
                [
                    'disable_workflow_restrictions' => true,
                    'data_class' => 'Test'
                ],
                true,
            ],
            [
                $formWithoutData,
                [
                    'disable_workflow_restrictions' => false,
                    'data_class' => 'Test'
                ],
                true,
            ],
        ];
    }

    public function testFinishViewWithDisallowRestriction()
    {
        $restrictions = [
            [
                'field' => 'status',
                'mode' => 'disallow',
                'values' => ['one', 'three'],
            ]
        ];

        $form = $this->factory->createBuilder('form', ['status' => 2])
            ->add('status', 'choice', ['choices' => ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4]])
            ->getForm();
        $expectedView = $form->createView();
        unset(
            $expectedView->children['status']->vars['choices'][0],
            $expectedView->children['status']->vars['choices'][2]
        );

        $this->restrictionsManager
                ->expects($this->once())
                ->method('hasEntityClassRestrictions')
                ->with('Test')
                ->willReturn(true);
        $this->restrictionsManager
            ->expects($this->once())
            ->method('getEntityRestrictions')
            ->with($form->getData())
            ->willReturn($restrictions);

        $view = $form->createView();
        $this->extension->finishView(
            $view,
            $form,
            [
                'disable_workflow_restrictions' => false,
                'data_class' => 'Test',
            ]
        );

        $this->assertEquals($expectedView, $view);
    }

    public function testFinishViewWithAllowRestriction()
    {
        $restrictions = [
            [
                'field' => 'status',
                'mode' => 'allow',
                'values' => ['one', 'two', 'three'],
            ]
        ];

        $form = $this->factory->createBuilder('form', ['status' => 2])
            ->add('status', 'choice', ['choices' => ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4]])
            ->getForm();
        $expectedView = $form->createView();
        unset(
            $expectedView->children['status']->vars['choices'][3]
        );

        $this->restrictionsManager
                ->expects($this->once())
                ->method('hasEntityClassRestrictions')
                ->with('Test')
                ->willReturn(true);
        $this->restrictionsManager
            ->expects($this->once())
            ->method('getEntityRestrictions')
            ->with($form->getData())
            ->willReturn($restrictions);

        $view = $form->createView();
        $this->extension->finishView(
            $view,
            $form,
            [
                'disable_workflow_restrictions' => false,
                'data_class' => 'Test',
            ]
        );

        $this->assertEquals($expectedView, $view);
    }

    public function testFinishViewWithFullRestriction()
    {
        $restrictions = [
            [
                'field' => 'status',
                'mode' => 'full',
                'values' => [],
            ]
        ];

        $form = $this->factory->createBuilder('form', ['status' => 2])
            ->add('status', 'choice', ['choices' => ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4]])
            ->getForm();
        $expectedView = $form->createView();
        $expectedView->children['status']->vars['attrs']['disabled'] = true;

        $this->restrictionsManager
                ->expects($this->once())
                ->method('hasEntityClassRestrictions')
                ->with('Test')
                ->willReturn(true);
        $this->restrictionsManager
            ->expects($this->once())
            ->method('getEntityRestrictions')
            ->with($form->getData())
            ->willReturn($restrictions);

        $view = $form->createView();
        $this->extension->finishView(
            $view,
            $form,
            [
                'disable_workflow_restrictions' => false,
                'data_class' => 'Test',
            ]
        );

        $this->assertEquals($expectedView, $view);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['disable_workflow_restrictions' => false]);

        $this->extension->configureOptions($resolver);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals('form', $this->extension->getExtendedType());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [new PreloadedExtension([], ['form' => [$this->extension]])];
    }
}
