<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\Step;

class StepTest extends \PHPUnit\Framework\TestCase
{
    /** @var Step */
    private $step;

    protected function setUp(): void
    {
        $this->step = new Step();
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testGettersAndSetters(string $property, mixed $value)
    {
        $getter = 'get' . ucfirst($property);
        $setter = 'set' . ucfirst($property);
        $this->assertInstanceOf(Step::class, call_user_func([$this->step, $setter], $value));
        $this->assertEquals($value, call_user_func_array([$this->step, $getter], []));
    }

    public function propertiesDataProvider(): array
    {
        return [
            'name' => ['name', 'test'],
            'order' => ['order', 1],
            'allowedTransitions' => ['allowedTransitions', ['one', 'two']],
            'label' => ['label', 'Value'],
        ];
    }

    public function testIsFinal()
    {
        $this->assertFalse($this->step->isFinal());
        $this->step->setFinal(true);
        $this->assertTrue($this->step->isFinal());
        $this->step->setFinal(false);
        $this->assertFalse($this->step->isFinal());
    }

    public function testAllowTransition()
    {
        $this->assertFalse($this->step->hasAllowedTransitions());
        $this->step->allowTransition('test');
        $this->assertTrue($this->step->hasAllowedTransitions());
        $this->assertEquals(['test'], $this->step->getAllowedTransitions(), 'Transition was not allowed');

        // Check duplicate
        $this->step->allowTransition('test');
        $this->assertEquals(
            ['test'],
            $this->step->getAllowedTransitions(),
            'Transition was allowed more than once'
        );

        // Check allowing more than one transition
        $this->step->allowTransition('test2');
        $this->assertEquals(
            ['test', 'test2'],
            $this->step->getAllowedTransitions(),
            'Second transition was not allowed'
        );

        // Check disallow
        $this->step->disallowTransition('test2');
        $this->assertEquals(['test'], $this->step->getAllowedTransitions(), 'Transition was not disallowed');

        // Check isAllowed
        $this->assertTrue($this->step->isAllowedTransition('test'), 'Expected transition not allowed');
        $this->assertFalse($this->step->isAllowedTransition('test2'), 'Unexpected transition allowed');
    }

    public function testEntityAclAllowed()
    {
        $this->assertTrue($this->step->isEntityUpdateAllowed('not_existing_attribute'));
        $this->assertTrue($this->step->isEntityDeleteAllowed('not_existing_attribute'));

        $this->step->setEntityAcls(['existing_attribute' => ['update' => false, 'delete' => false]]);
        $this->assertFalse($this->step->isEntityUpdateAllowed('existing_attribute'));
        $this->assertFalse($this->step->isEntityDeleteAllowed('existing_attribute'));

        $this->step->setEntityAcls(['existing_attribute' => ['update' => true, 'delete' => true]]);
        $this->assertTrue($this->step->isEntityUpdateAllowed('existing_attribute'));
        $this->assertTrue($this->step->isEntityDeleteAllowed('existing_attribute'));
    }
}
