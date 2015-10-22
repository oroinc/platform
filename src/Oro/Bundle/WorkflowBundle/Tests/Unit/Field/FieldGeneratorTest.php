<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Field;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\WorkflowBundle\Field\FieldGenerator;

class FieldGeneratorTest extends \PHPUnit_Framework_TestCase
{
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
            $this->configManager,
            $this->entityProcessor,
            $this->entityConnector
        );
    }

    protected function tearDown()
    {
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

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->any())->method('getConfig')->with($entityClass)
            ->will($this->returnValue($entityConfig));

        $this->configManager->expects($this->any())->method('getProvider')->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $this->generator->generateWorkflowFields($entityClass);
    }

    public function testGenerateWorkflowFields()
    {
        $entityClass = 'TestEntity';

        $this->entityConnector->expects($this->once())->method('isWorkflowAware')->with($entityClass)
            ->will($this->returnValue(false));

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $formConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $viewConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $importExportConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $providerMap = array(
            array('extend', $extendConfigProvider),
            array('entity', $entityConfigProvider),
            array('form', $formConfigProvider),
            array('view', $viewConfigProvider),
            array('importexport', $importExportConfigProvider),
        );
        $this->configManager->expects($this->any())->method('getProvider')->will($this->returnValueMap($providerMap));

        $entityConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $entityConfig->expects($this->at(0))->method('is')->with('is_extend')->will($this->returnValue(true));

        $extendConfigProvider->expects($this->at(0))->method('getConfig')->with($entityClass)
            ->will($this->returnValue($entityConfig));

        $workflowItemClass = 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem';
        $workflowStepClass = 'Oro\Bundle\WorkflowBundle\Entity\WorkflowStep';

        $this->addFieldAssertions(
            $entityConfigProvider,
            $extendConfigProvider,
            $formConfigProvider,
            $viewConfigProvider,
            $importExportConfigProvider,
            $entityClass,
            FieldGenerator::PROPERTY_WORKFLOW_ITEM,
            ConfigHelper::getTranslationKey('entity', 'label', $workflowItemClass, 'related_entity'),
            ConfigHelper::getTranslationKey('entity', 'description', $workflowItemClass, 'related_entity'),
            'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
            'id',
            0
        );
        $this->addFieldAssertions(
            $entityConfigProvider,
            $extendConfigProvider,
            $formConfigProvider,
            $viewConfigProvider,
            $importExportConfigProvider,
            $entityClass,
            FieldGenerator::PROPERTY_WORKFLOW_STEP,
            ConfigHelper::getTranslationKey('entity', 'label', $workflowStepClass, 'related_entity'),
            ConfigHelper::getTranslationKey('entity', 'description', $workflowStepClass, 'related_entity'),
            'Oro\Bundle\WorkflowBundle\Entity\WorkflowStep',
            'label',
            1
        );

        $entityConfig->expects($this->at(1))->method('set')->with('state', ExtendScope::STATE_UPDATE);
        $entityConfig->expects($this->at(2))->method('set')->with('upgradeable', true);

        $this->configManager->expects($this->at(15))->method('persist')->with($entityConfig);
        $this->configManager->expects($this->at(16))->method('flush');

        $this->entityProcessor->expects($this->once())->method('updateDatabase');

        /*
        $this->addHideAssertions($entityClass, FieldGenerator::PROPERTY_WORKFLOW_ITEM, 0);
        $this->addHideAssertions($entityClass, FieldGenerator::PROPERTY_WORKFLOW_STEP, 1);
        $this->configManager->expects($this->at(17))->method('flush');
        */

        // run test
        $this->generator->generateWorkflowFields($entityClass);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $entityConfigProvider
     * @param \PHPUnit_Framework_MockObject_MockObject $extendConfigProvider
     * @param \PHPUnit_Framework_MockObject_MockObject $formConfigProvider
     * @param \PHPUnit_Framework_MockObject_MockObject $viewConfigProvider
     * @param \PHPUnit_Framework_MockObject_MockObject $importExportConfigProvider
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
        \PHPUnit_Framework_MockObject_MockObject $importExportConfigProvider,
        $entityClass,
        $fieldName,
        $label,
        $description,
        $targetEntity,
        $targetField,
        $iteration
    ) {
        $this->configManager->expects($this->at($iteration * 7 + 1))->method('hasConfig')
            ->with($entityClass, $fieldName)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->at($iteration * 7 + 2))->method('createConfigFieldModel')
            ->with($entityClass, $fieldName, 'manyToOne');

        $entityFieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $entityFieldConfig->expects($this->at(0))->method('set')->with('label', $label);
        $entityFieldConfig->expects($this->at(1))->method('set')->with('description', $description);
        $entityConfigProvider->expects($this->at($iteration))->method('getConfig')->with($entityClass, $fieldName)
            ->will($this->returnValue($entityFieldConfig));

        $extendFieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $extendFieldConfig->expects($this->at(0))->method('set')->with('owner', ExtendScope::OWNER_CUSTOM);
        $extendFieldConfig->expects($this->at(1))->method('set')->with('state', ExtendScope::STATE_NEW);
        $extendFieldConfig->expects($this->at(2))->method('set')->with('is_extend', true);
        $extendFieldConfig->expects($this->at(3))->method('set')->with('target_entity', $targetEntity);
        $extendFieldConfig->expects($this->at(4))->method('set')->with('target_field', $targetField);
        $extendFieldConfig->expects($this->at(5))->method('set')->with(
            'relation_key',
            ExtendHelper::buildRelationKey($entityClass, $targetField, 'manyToOne', $targetEntity)
        );
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

        $importExportFieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $importExportFieldConfig->expects($this->at(0))->method('set')->with('excluded', true);
        $importExportConfigProvider->expects($this->at($iteration))->method('getConfig')->with($entityClass, $fieldName)
            ->will($this->returnValue($importExportFieldConfig));
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     * @param int $iteration
     */
    protected function addHideAssertions($entityClass, $fieldName, $iteration)
    {
        $fieldModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')->getMock();
        $fieldModel->expects($this->once())->method('setType')->with(ConfigModel::MODE_HIDDEN);

        $this->configManager->expects($this->at(17 + $iteration))->method('getConfigFieldModel')
            ->with($entityClass, $fieldName)
            ->will($this->returnValue($fieldModel));
    }
}
