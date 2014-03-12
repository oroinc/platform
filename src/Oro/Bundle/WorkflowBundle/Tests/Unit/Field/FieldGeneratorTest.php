<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Field;

use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;
use Oro\Bundle\WorkflowBundle\Field\FieldGenerator;

class FieldGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityProcessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityConnector;

    /**
     * @var FieldGenerator
     */
    protected $generator;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityProcessor = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConnector = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\EntityConnector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->generator = new FieldGenerator(
            $this->translator,
            $this->configManager,
            $this->entityProcessor,
            $this->entityConnector
        );
    }

    protected function tearDown()
    {
        unset($this->translator);
        unset($this->configManager);
        unset($this->entityProcessor);
        unset($this->entityConnector);
        unset($this->generator);
    }

    public function testGenerateWorkflowFieldsForWorkflowAwareEntity()
    {
        $entityClass = 'TestEntity';

        $this->entityConnector->expects($this->once())->method('isWorkflowAware')->with($entityClass)
            ->will($this->returnValue(true));

        $this->configManager->expects($this->never())->method('getProvider');

        $this->generator->generateWorkflowFields($entityClass);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Class TestEntity can not be extended
     */
    public function testGenerateWorkflowFieldsNotExtendedClass()
    {
        $entityClass = 'TestEntity';

        $this->entityConnector->expects($this->once())->method('isWorkflowAware')->with($entityClass)
            ->will($this->returnValue(false));

        $entityConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $entityConfig->expects($this->once())->method('is')->with('is_extend')
            ->will($this->returnValue(false));

        $extendConfigProvider = $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');
        $extendConfigProvider->expects($this->any())->method('getConfig')->with($entityClass)
            ->will($this->returnValue($entityConfig));

        $this->configManager->expects($this->any())->method('getProvider')->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $this->generator->generateWorkflowFields($entityClass);
    }

    public function testGenerateWorkflowFields()
    {
        $entityClass = 'TestEntity';

        $this->translator->expects($this->any())->method('trans')->will(
            $this->returnCallback(
                function ($id) {
                    return $id . '.translated';
                }
            )
        );

        $this->entityConnector->expects($this->once())->method('isWorkflowAware')->with($entityClass)
            ->will($this->returnValue(false));

        $extendConfigProvider = $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');
        $entityConfigProvider = $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');
        $formConfigProvider = $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');
        $viewConfigProvider = $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');

        $providerMap = array(
            array('extend', $extendConfigProvider),
            array('entity', $entityConfigProvider),
            array('form', $formConfigProvider),
            array('view', $viewConfigProvider),
        );
        $this->configManager->expects($this->any())->method('getProvider')->will($this->returnValueMap($providerMap));

        $entityConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $entityConfig->expects($this->at(0))->method('is')->with('is_extend')->will($this->returnValue(true));

        $extendConfigProvider->expects($this->at(0))->method('getConfig')->with($entityClass)
            ->will($this->returnValue($entityConfig));

        $this->addFieldAssertions(
            $entityConfigProvider,
            $extendConfigProvider,
            $formConfigProvider,
            $viewConfigProvider,
            $entityClass,
            FieldGenerator::PROPERTY_WORKFLOW_ITEM,
            'oro.workflow.workflowitem.entity_label',
            'oro.workflow.workflowitem.entity_description',
            'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
            'id',
            0
        );
        $this->addFieldAssertions(
            $entityConfigProvider,
            $extendConfigProvider,
            $formConfigProvider,
            $viewConfigProvider,
            $entityClass,
            FieldGenerator::PROPERTY_WORKFLOW_STEP,
            'oro.workflow.workflowstep.entity_label',
            'oro.workflow.workflowstep.entity_description',
            'Oro\Bundle\WorkflowBundle\Entity\WorkflowStep',
            'label',
            1
        );

        $entityConfig->expects($this->at(1))->method('set')->with('state', ExtendManager::STATE_UPDATED);
        $entityConfig->expects($this->at(2))->method('set')->with('upgradeable', true);

        $this->configManager->expects($this->at(13))->method('persist')->with($entityConfig);
        $this->configManager->expects($this->at(14))->method('flush');

        $this->entityProcessor->expects($this->once())->method('updateDatabase');

        // run test
        $this->generator->generateWorkflowFields($entityClass);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $entityConfigProvider
     * @param \PHPUnit_Framework_MockObject_MockObject $extendConfigProvider
     * @param \PHPUnit_Framework_MockObject_MockObject $formConfigProvider
     * @param \PHPUnit_Framework_MockObject_MockObject $viewConfigProvider
     * @param string $entityClass
     * @param string $fieldName
     * @param string $label
     * @param string $description
     * @param string $targetEntity
     * @param string $targetField
     * @param int $iteration
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function addFieldAssertions(
        \PHPUnit_Framework_MockObject_MockObject $entityConfigProvider,
        \PHPUnit_Framework_MockObject_MockObject $extendConfigProvider,
        \PHPUnit_Framework_MockObject_MockObject $formConfigProvider,
        \PHPUnit_Framework_MockObject_MockObject $viewConfigProvider,
        $entityClass,
        $fieldName,
        $label,
        $description,
        $targetEntity,
        $targetField,
        $iteration
    ) {
        $this->configManager->expects($this->at($iteration * 6 + 1))->method('hasConfigFieldModel')
            ->with($entityClass, $fieldName)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->at($iteration * 6 + 2))->method('createConfigFieldModel')
            ->with($entityClass, $fieldName, 'manyToOne');

        $entityFieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $entityFieldConfig->expects($this->at(0))->method('set')->with('label', $label . '.translated');
        $entityFieldConfig->expects($this->at(1))->method('set')->with('description', $description . '.translated');
        $entityConfigProvider->expects($this->at($iteration))->method('getConfig')->with($entityClass, $fieldName)
            ->will($this->returnValue($entityFieldConfig));

        $extendFieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $extendFieldConfig->expects($this->at(0))->method('set')->with('owner', ExtendManager::OWNER_CUSTOM);
        $extendFieldConfig->expects($this->at(1))->method('set')->with('state', ExtendManager::STATE_NEW);
        $extendFieldConfig->expects($this->at(2))->method('set')->with('extend', true);
        $extendFieldConfig->expects($this->at(3))->method('set')->with('target_entity', $targetEntity);
        $extendFieldConfig->expects($this->at(4))->method('set')->with('target_field', $targetField);
        $extendConfigProvider->expects($this->at($iteration + 1))->method('getConfig')->with($entityClass, $fieldName)
            ->will($this->returnValue($extendFieldConfig));

        $formFieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $formFieldConfig->expects($this->at(0))->method('set')->with('is_enabled', false);
        $formConfigProvider->expects($this->at($iteration))->method('getConfig')->with($entityClass, $fieldName)
            ->will($this->returnValue($formFieldConfig));

        $viewFieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $viewFieldConfig->expects($this->at(0))->method('set')->with('is_displayable', false);
        $viewConfigProvider->expects($this->at($iteration))->method('getConfig')->with($entityClass, $fieldName)
            ->will($this->returnValue($viewFieldConfig));
    }
}
