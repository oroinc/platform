<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TransitionTriggerEventTest extends AbstractTransitionTriggerTestCase
{
    public function testAccessors()
    {
        parent::testAccessors();

        $this->assertPropertyAccessors($this->entity, [
            ['entityClass', 'test_entity'],
            ['event', 'test_event'],
            ['field', 'test_field'],
            ['require', 'test_require'],
            ['relation', 'test_relation'],
        ]);
    }

    public function testImport()
    {
        $trigger = $this->getEntity();
        /** @var TransitionTriggerEvent $entity */
        $entity = $this->entity;
        $this->setDataToTrigger($trigger);
        $trigger->setEvent('test_event')
            ->setEntityClass('test_entity')
            ->setRelation('test_relation')
            ->setRequire('test_require')
            ->setField('test_field');
        $entity->import($trigger);
        $this->assertImportData();
        $this->assertEquals($trigger->getEvent(), $entity->getEvent());
        $this->assertEquals($trigger->getEntityClass(), $entity->getEntityClass());
        $this->assertEquals($trigger->getRelation(), $entity->getRelation());
        $this->assertEquals($trigger->getRequire(), $entity->getRequire());
        $this->assertEquals($trigger->getField(), $entity->getField());
    }

    /**
     * @dataProvider equalityData
     * @param bool $expected
     * @param array $match
     * @param array $against
     */
    public function testIsEqual($expected, array $match, array $against)
    {

        $this->assertEquals(
            $expected,
            $this->createTriggerEvent($match)->isEqualTo(
                $this->createTriggerEvent($against)
            )
        );
    }

    /**
     * @return array
     */
    public function equalityData()
    {
        $workflowDefinitionOne = (new WorkflowDefinition())->setName('one');
        $workflowDefinitionTwo = (new WorkflowDefinition())->setName('two');
        $workflowDefinitionSemiTwo = (new WorkflowDefinition())->setName('two');

        $fieldsSets = [
            'workflowDefinition' => [$workflowDefinitionOne, $workflowDefinitionTwo],
            'transitionName' => ['one', 'two'],
            'event' => ['create', 'update'],
            'field' => ['property_one', 'property_two'],
            'entityClass' => ['class1', 'class2'],
            'require' => ['expr1', 'expr2'],
            'relation' => ['path1', 'path2'],
            'queued' => [false, true]
        ];

        $cases = [
            'all same' => [
                true,
                [
                    'workflowDefinition' => $workflowDefinitionOne,
                    'transitionName' => 't1',
                    'event' => 'update',
                    'field' => 'some',
                    'entityClass' => \stdClass::class,
                    'require' => 'expression',
                    'relation' => 'relation',
                    'queued' => true
                ],
                [
                    'workflowDefinition' => $workflowDefinitionOne,
                    'transitionName' => 't1',
                    'event' => 'update',
                    'field' => 'some',
                    'entityClass' => \stdClass::class,
                    'require' => 'expression',
                    'relation' => 'relation',
                    'queued' => true
                ]
            ],
            'empty same' => [
                true,
                [],
                []
            ],
            'wf definitions' => [
                false,
                ['workflowDefinition' => $workflowDefinitionTwo],
                ['workflowDefinition' => $workflowDefinitionSemiTwo],
            ]
        ];

        $nullsExceptOne = [];

        foreach ($fieldsSets as $field => $values) {
            $caseTrueName = sprintf('all null and %s is equals', $field);
            list($first, $second) = $values;
            $nullsExceptOne[$caseTrueName] = [
                true,
                [$field => $first],
                [$field => $first],
            ];
            $caseFalseName = sprintf('all null and %s not equals', $field);
            $nullsExceptOne[$caseFalseName] = [
                false,
                [$field => $first],
                [$field => $second]
            ];
        }

        $cases = array_merge($cases, $nullsExceptOne);

        $oneDiffers = [];

        foreach ($fieldsSets as $currentField => $currentValues) {
            $caseName = sprintf('all equals except %s', $currentField);
            $proto = [];
            foreach ($fieldsSets as $field => $values) {
                if ($field === $currentField) {
                    $proto[$field] = $values[0];
                }
            }

            $diff = $proto;
            $diff[$currentField] = $currentValues[1];

            $oneDiffers[$caseName] = [
                false,
                $proto,
                $diff
            ];
        }

        $cases = array_merge($cases, $oneDiffers);

        return $cases;
    }

    /**
     * @param array $attributes
     * @return TransitionTriggerEvent
     */
    protected function createTriggerEvent(array $attributes)
    {
        $trigger = new TransitionTriggerEvent();

        foreach ($attributes as $name => $value) {
            $method = 'set' . ucfirst($name);
            $trigger->$method($value);
        }

        return $trigger;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return new TransitionTriggerEvent();
    }
}
