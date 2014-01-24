<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepAssembler;
use Oro\Bundle\WorkflowBundle\Model\Attribute;

class StepAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StepAssembler
     */
    protected $assembler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->getMock();

        $this->assembler = new StepAssembler($this->container);
    }

    protected function getWorkflowDefinitionMock()
    {
        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('test_workflow'));
        return $definition;
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\AssemblerException
     * @dataProvider invalidOptionsDataProvider
     * @param array $configuration
     */
    public function testAssembleRequiredOptionException($configuration)
    {
        $this->assembler->assemble($this->getWorkflowDefinitionMock(), $configuration, null);
    }

    public function invalidOptionsDataProvider()
    {
        return array(
            'no options' => array(
                array(
                    'name' => array()
                )
            ),
            'no label' => array(
                array(
                    'name' => array(
                        'isFinal' => false
                    )
                )
            )
        );
    }

    public function assertEntityStepCalls($stepName = 'entity_step')
    {
        $stepEntity = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep')
            ->disableOriginalConstructor()
            ->getMock();
        $stepEntity->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($stepName));
        $stepEntities = array($stepEntity);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findBy')
            ->will($this->returnValue($stepEntities));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroWorkflowBundle:WorkflowStep')
            ->will($this->returnValue($repository));
        $this->container->expects($this->once())
            ->method('get')
            ->with('doctrine.orm.default_entity_manager')
            ->will($this->returnValue($em));

        return $stepEntity;
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testAssemble($configuration, $attributes, Step $expectedStep)
    {
        $stepEntity = $this->assertEntityStepCalls($expectedStep->getName());
        $expectedStep->setEntity($stepEntity);
        $configurationPass = $this->getMockBuilder(
            'Oro\Bundle\WorkflowBundle\Model\ConfigurationPass\ConfigurationPassInterface'
        )->getMockForAbstractClass();

        $configurationPass->expects($this->any())
            ->method('passConfiguration')
            ->with($this->isType('array'))
            ->will(
                $this->returnCallback(
                    function (array $data) {
                        if (isset($data['path'])) {
                            $data['path'] = new PropertyPath('data.' . str_replace('$', '', $data['path']));
                        } else {
                            foreach ($data as &$value) {
                                $value = new PropertyPath('data.' . str_replace('$', '', $value));
                            }
                        }
                        return $data;
                    }
                )
            );

        $this->assembler->addConfigurationPass($configurationPass);

        $expectedAttributes = array();
        /** @var Attribute $attribute */
        foreach ($attributes ? $attributes : array() as $attribute) {
            $expectedAttributes[$attribute->getName()] = $attribute;
        }

        $steps = $this->assembler->assemble($this->getWorkflowDefinitionMock(), $configuration, $attributes);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $steps);
        $this->assertCount(1, $steps);
        $this->assertTrue($steps->containsKey($expectedStep->getName()));

        $this->assertEquals($expectedStep, $steps->get($expectedStep->getName()));
    }

    public function configurationDataProvider()
    {
        return array(
            'minimal' => array(
                array(
                    'step_one' => array(
                        'label' => 'label',
                    )
                ),
                null,
                $this->createStep('step_one')
                    ->setLabel('label')
                    ->setOrder(0)
                    ->setIsFinal(false),
            ),
            'full' => array(
                array(
                    'step_two' => array(
                        'label' => 'label',
                        'order' => 10,
                        'is_final' => true,
                        'allowed_transitions' => array('transition_one'),
                    )
                ),
                array(
                    $this->createAttribute('attribute_one')->setLabel('Attribute One'),
                    $this->createAttribute('attribute_two'),
                ),
                $this->createStep('step_two')
                    ->setLabel('label')
                    ->setIsFinal(true)
                    ->setOrder(10)
                    ->setAllowedTransitions(array('transition_one'))
            ),
        );
    }

    /**
     * @param string $name
     * @return Step
     */
    protected function createStep($name)
    {
        $step = new Step();
        $step->setName($name);

        return $step;
    }

    /**
     * @param string $name
     * @return Attribute
     */
    protected function createAttribute($name)
    {
        $attribute = new Attribute();
        $attribute->setName($name);

        return $attribute;
    }
}
