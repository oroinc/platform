<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Form\Extension\RestrictionsExtension;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class RestrictionsExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $workflowManager;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var RestrictionManager|\PHPUnit\Framework\MockObject\MockObject
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
     * @dataProvider buildFormDataProvider
     *
     * @param array $options
     * @param array $fields
     * @param array $restrictions
     */
    public function testBuildForm(array $options, array $fields = [], array $restrictions = [])
    {
        $hasRestrictions = !empty($restrictions);
        $data            = (object)[1];

        if (!empty($options['data_class']) &&
            empty($options['disable_workflow_restrictions']) &&
            $hasRestrictions
        ) {
            $this->restrictionsManager
                ->expects($this->once())
                ->method('hasEntityClassRestrictions')
                ->with($options['data_class'])
                ->willReturn($hasRestrictions);
            $this->restrictionsManager
                ->expects($this->once())
                ->method('getEntityRestrictions')
                ->with($data)
                ->willReturn($restrictions);
        }
        $builder = $this->factory->createNamedBuilder('test_entity');
        foreach ($fields as $field) {
            $builder->add($field['name'], null, []);
        }
        $form = $builder->getForm();
        $this->extension->buildForm($builder, $options);
        $dispatcher = $form->getConfig()->getEventDispatcher();
        $event = new FormEvent($form, $data);
        $dispatcher->dispatch(FormEvents::POST_SET_DATA, $event);

        foreach ($fields as $field) {
            $this->assertEquals(
                $field['expectedAttr'],
                $form->get($field['name'])->getConfig()->getOption('attr')
            );
        }
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['disable_workflow_restrictions' => false]);

        $this->extension->configureOptions($resolver);
    }

    public function buildFormDataProvider()
    {
        return [
            'enabled extension'          => [
                ['disable_workflow_restrictions' => false, 'data_class' => 'test'],
                [
                    ['name' => 'test_field_1', 'expectedAttr' => ['readonly' => true]],
                    ['name' => 'test_field_2', 'expectedAttr' => []]
                ],
                [
                    ['field' => 'test_field_1', 'mode' => 'full'],
                ]
            ],
            'no fields for restrictions' => [
                ['disable_workflow_restrictions' => false, 'data_class' => 'test'],
                [
                    ['name' => 'test_field_1', 'expectedAttr' => []],
                    ['name' => 'test_field_2', 'expectedAttr' => []]
                ],
                [
                    ['field' => 'test_field_3', 'mode' => 'full'],
                ]
            ],
            'disabled extension'         => [
                ['disable_workflow_restrictions' => false],
                [
                    ['name' => 'test_field_1', 'expectedAttr' => []],
                    ['name' => 'test_field_2', 'expectedAttr' => []]
                ],
            ],
            'no data_class option'       => [
                ['disable_workflow_restrictions' => true],
                [
                    ['name' => 'test_field_1', 'expectedAttr' => []],
                    ['name' => 'test_field_2', 'expectedAttr' => []]
                ],
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [new PreloadedExtension([], [FormType::class => [$this->extension]])];
    }
}
