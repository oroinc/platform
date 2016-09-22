<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TransitionEventTriggerTest extends AbstractTransitionTriggerTestCase
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
        /** @var TransitionEventTrigger $entity */
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
     * @dataProvider toStringDataProvider
     *
     * @param array $data
     * @param string $expected
     */
    public function testToString(array $data, $expected)
    {
        $this->assertEquals($expected, (string) $this->createEventTrigger($data));
    }

    /**
     * @return array
     */
    public function toStringDataProvider()
    {
        $wd1 = (new WorkflowDefinition())->setName('wd1');

        return [
            'not queued' => [
                'data' => [
                    'event' => 'create',
                    'field' => 'property_one',
                    'entityClass' => 'class1',
                    'require' => 'expr1',
                    'relation' => 'path1',
                    'queued' => false,
                    'workflowDefinition' => $wd1,
                    'transitionName' => 't1'
                ],
                'expected' => 'event: [wd1:t1](on:create[property_one]):=>path1:expr(expr1):RUNTIME',
            ],
            'queued, no require, no relation' => [
                'data' => [
                    'event' => 'create',
                    'field' => 'property_one',
                    'entityClass' => 'class1',
                    'queued' => true,
                    'workflowDefinition' => $wd1,
                    'transitionName' => 't1'
                ],
                'expected' => 'event: [wd1:t1](on:create[property_one]):MQ',
            ],
        ];
    }

    /**
     * @dataProvider equalityDataProvider
     * @param bool $expected
     * @param array $match
     * @param array $against
     */
    public function testIsEqual($expected, array $match, array $against)
    {

        $this->assertEquals(
            $expected,
            $this->createEventTrigger($match)->isEqualTo(
                $this->createEventTrigger($against)
            )
        );
    }

    /**
     * @return array
     */
    public function equalityDataProvider()
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
                true,
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

    public function testGetEntityClass()
    {
        $trigger = new TransitionEventTrigger();

        $this->assertNull($trigger->getEntityClass());

        $definition = new WorkflowDefinition();
        $definition->setRelatedEntity('test class name');

        $trigger->setWorkflowDefinition($definition);
        $this->assertEquals($definition->getRelatedEntity(), $trigger->getEntityClass());

        $trigger->setEntityClass('stdClass');
        $this->assertEquals('stdClass', $trigger->getEntityClass());
    }

    /**
     * @param array $attributes
     * @return TransitionEventTrigger
     */
    protected function createEventTrigger(array $attributes)
    {
        $trigger = new TransitionEventTrigger();

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
        return new TransitionEventTrigger();
    }
}
