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

    protected function setUp()
    {
        $this->assembler = new StepAssembler();
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\AssemblerException
     * @dataProvider invalidOptionsDataProvider
     * @param array $configuration
     */
    public function testAssembleRequiredOptionException($configuration)
    {
        $this->assembler->assemble($configuration, null);
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

    /**
     * @dataProvider configurationDataProvider
     */
    public function testAssemble($configuration, $attributes, Step $expectedStep)
    {
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

        $steps = $this->assembler->assemble($configuration, $attributes);
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
                    ->setFinal(false),
            ),
            'full' => array(
                array(
                    'step_two' => array(
                        'label' => 'label',
                        'order' => 10,
                        'is_final' => true,
                        'allowed_transitions' => array('transition_one'),
                        'entity_acl' => array(
                            'attribute_one' => array('update' => false)
                        )
                    )
                ),
                array(
                    $this->createAttribute('attribute_one')->setLabel('Attribute One'),
                    $this->createAttribute('attribute_two'),
                ),
                $this->createStep('step_two')
                    ->setLabel('label')
                    ->setFinal(true)
                    ->setOrder(10)
                    ->setAllowedTransitions(array('transition_one'))
                    ->setEntityAcls(array('attribute_one' => array('update' => false)))
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
