<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\WorkflowBundle\Form\Extension\RestrictionsExtension;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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
     * @dataProvider testBuildFormDataProvider
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
                $field['read_only'],
                $form->get($field['name'])->getConfig()->getOption('read_only')
            );
        }
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

    public function testBuildFormDataProvider()
    {
        return [
            'enabled extension'          => [
                ['disable_workflow_restrictions' => false, 'data_class' => 'test'],
                [
                    ['name' => 'test_field_1', 'read_only' => true],
                    ['name' => 'test_field_2', 'read_only' => false]
                ],
                [
                    ['field' => 'test_field_1', 'mode' => 'full'],
                ]
            ],
            'no fields for restrictions' => [
                ['disable_workflow_restrictions' => false, 'data_class' => 'test'],
                [
                    ['name' => 'test_field_1', 'read_only' => false],
                    ['name' => 'test_field_2', 'read_only' => false]
                ],
                [
                    ['field' => 'test_field_3', 'mode' => 'full'],
                ]
            ],
            'disabled extension'         => [
                ['disable_workflow_restrictions' => false],
                [
                    ['name' => 'test_field_1', 'read_only' => false],
                    ['name' => 'test_field_2', 'read_only' => false]
                ],
            ],
            'no data_class option'       => [
                ['disable_workflow_restrictions' => true],
                [
                    ['name' => 'test_field_1', 'read_only' => false],
                    ['name' => 'test_field_2', 'read_only' => false]
                ],
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [new PreloadedExtension([], ['form' => [$this->extension]])];
    }
}
