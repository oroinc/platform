<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\FieldGenerator;

use Oro\Bundle\WorkflowBundle\Generator\FieldGenerator;

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
}
