<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;
use Oro\Bundle\WorkflowBundle\Form\Type\ApplicableEntitiesType;

class ApplicableEntitiesTypeTest extends AbstractWorkflowAttributesTypeTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityConnector;

    /**
     * @var ApplicableEntitiesType
     */
    protected $formType;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConnector = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\EntityConnector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new ApplicableEntitiesType($this->configManager, $this->entityConnector);
    }

    protected function tearDown()
    {
        unset($this->configManager);
        unset($this->entityConnector);
        unset($this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals(ApplicableEntitiesType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityChoiceType::NAME, $this->formType->getParent());
    }

    public function testChoiceNormalizer()
    {
        // source data
        $workflowAwareClass = 'WorkflowAwareClass';
        $extendedClass = 'ExtendedClass';
        $notExtendedClass = 'NotExtendedClass';
        $notConfigurableClass = 'NotConfigurableClass';

        // asserts
        $this->entityConnector->expects($this->any())->method('isWorkflowAware')
            ->will(
                $this->returnCallback(
                    function ($class) use ($workflowAwareClass) {
                        return $class === $workflowAwareClass;
                    }
                )
            );

        $extendedEntityConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $extendedEntityConfig->expects($this->any())->method('is')->with('is_extend')
            ->will($this->returnValue(true));

        $notExtendedEntityConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $notExtendedEntityConfig->expects($this->any())->method('is')->with('is_extend')
            ->will($this->returnValue(false));

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $hasConfigMap = array(
            array($workflowAwareClass, null, false),
            array($extendedClass, null, true),
            array($notExtendedClass, null, true),
            array($notConfigurableClass, null, false),
        );
        $extendConfigProvider->expects($this->any())->method('hasConfig')->with($this->isType('string'), null)
            ->will($this->returnValueMap($hasConfigMap));
        $getConfigMap = array(
            array($extendedClass, null, $extendedEntityConfig),
            array($notExtendedClass, null, $notExtendedEntityConfig),
        );
        $extendConfigProvider->expects($this->any())->method('getConfig')->with($this->isType('string'), null)
            ->will($this->returnValueMap($getConfigMap));

        $this->configManager->expects($this->once())->method('getProvider')->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        // test
        $inputChoices = array(
            $workflowAwareClass => Inflector::tableize($workflowAwareClass),
            $extendedClass => Inflector::tableize($extendedClass),
            $notExtendedClass => Inflector::tableize($notExtendedClass),
            $notConfigurableClass => Inflector::tableize($notConfigurableClass)
        );
        $expectedChoices = array(
            $workflowAwareClass => Inflector::tableize($workflowAwareClass),
            $extendedClass => Inflector::tableize($extendedClass)
        );

        $resolver = new OptionsResolver();
        $resolver->setDefaults(array('choices' => $inputChoices));
        $this->formType->setDefaultOptions($resolver);
        $result = $resolver->resolve(array());
        $this->assertEquals($expectedChoices, $result['choices']);
    }
}
