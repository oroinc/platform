<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepAssembler;
use Oro\Component\Action\Exception\AssemblerException;
use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class StepAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var StepAssembler */
    private $assembler;

    protected function setUp(): void
    {
        $this->assembler = new StepAssembler();
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     * @param array $configuration
     */
    public function testAssembleRequiredOptionException($configuration)
    {
        $this->expectException(AssemblerException::class);
        $this->assembler->assemble($configuration, null);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            'no options' => [
                [
                    'name' => []
                ]
            ],
            'no label' => [
                [
                    'name' => [
                        'isFinal' => false
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testAssemble($configuration, $attributes, Step $expectedStep)
    {
        $configurationPass = $this->createMock(ConfigurationPassInterface::class);

        $configurationPass->expects($this->any())
            ->method('passConfiguration')
            ->with($this->isType('array'))
            ->willReturnCallback(function (array $data) {
                if (isset($data['path'])) {
                    $data['path'] = new PropertyPath('data.' . str_replace('$', '', $data['path']));
                } else {
                    foreach ($data as &$value) {
                        $value = new PropertyPath('data.' . str_replace('$', '', $value));
                    }
                }

                return $data;
            });

        $this->assembler->addConfigurationPass($configurationPass);

        $expectedAttributes = [];
        /** @var Attribute $attribute */
        foreach ($attributes ? $attributes : [] as $attribute) {
            $expectedAttributes[$attribute->getName()] = $attribute;
        }

        $steps = $this->assembler->assemble($configuration, $attributes);
        $this->assertInstanceOf(ArrayCollection::class, $steps);
        $this->assertCount(1, $steps);
        $this->assertTrue($steps->containsKey($expectedStep->getName()));

        $this->assertEquals($expectedStep, $steps->get($expectedStep->getName()));
    }

    public function configurationDataProvider(): array
    {
        return [
            'minimal' => [
                [
                    'step_one' => [
                        'label' => 'label',
                    ]
                ],
                null,
                $this->createStep('step_one')
                    ->setLabel('label')
                    ->setOrder(0)
                    ->setFinal(false),
            ],
            'full' => [
                [
                    'step_two' => [
                        'label' => 'label',
                        'order' => 10,
                        'is_final' => true,
                        'allowed_transitions' => ['transition_one'],
                        'entity_acl' => [
                            'attribute_one' => ['update' => false]
                        ]
                    ]
                ],
                [
                    $this->createAttribute('attribute_one')->setLabel('Attribute One'),
                    $this->createAttribute('attribute_two'),
                ],
                $this->createStep('step_two')
                    ->setLabel('label')
                    ->setFinal(true)
                    ->setOrder(10)
                    ->setAllowedTransitions(['transition_one'])
                    ->setEntityAcls(['attribute_one' => ['update' => false]])
            ],
        ];
    }

    /**
     * @param string $name
     * @return Step
     */
    private function createStep($name)
    {
        $step = new Step();
        $step->setName($name);

        return $step;
    }

    /**
     * @param string $name
     * @return Attribute
     */
    private function createAttribute($name)
    {
        $attribute = new Attribute();
        $attribute->setName($name);

        return $attribute;
    }
}
