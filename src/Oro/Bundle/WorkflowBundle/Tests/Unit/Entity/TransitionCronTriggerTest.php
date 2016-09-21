<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TransitionCronTriggerTest extends AbstractTransitionTriggerTestCase
{
    public function testAccessors()
    {
        parent::testAccessors();

        $this->assertPropertyAccessors($this->entity, [
            ['cron', 'test_cron'],
            ['filter', 'test_filter'],
        ]);
    }

    public function testImport()
    {
        $trigger = $this->getEntity();
        /** @var TransitionCronTrigger $entity */
        $entity = $this->entity;
        $this->setDataToTrigger($trigger);
        $trigger->setCron('test_cron')
            ->setFilter('test_filter');
        $entity->import($trigger);
        $this->assertImportData();
        $this->assertEquals($trigger->getCron(), $entity->getCron());
        $this->assertEquals($trigger->getFilter(), $entity->getFilter());
    }

    /**
     * @dataProvider toStringDataProvider
     *
     * @param array $data
     * @param string $expected
     */
    public function testToString(array $data, $expected)
    {
        $this->assertEquals($expected, (string) $this->createTriggerCron($data));
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
                    'cron' => '1 * * * *',
                    'filter' => 'w = 1',
                    'queued' => false,
                    'workflowDefinition' => $wd1,
                    'transitionName' => 't1'
                ],
                'expected' => 'cron: [wd1:t1](1 * * * *):w = 1:RUNTIME',
            ],
            'queued' => [
                'data' => [
                    'cron' => '1 * * * *',
                    'filter' => 'w = 1',
                    'queued' => true,
                    'workflowDefinition' => $wd1,
                    'transitionName' => 't1'
                ],
                'expected' => 'cron: [wd1:t1](1 * * * *):w = 1:MQ',
            ],
        ];
    }

    /**
     * @dataProvider equalityDataProvider
     *
     * @param bool $expected
     * @param array $match
     * @param array $against
     */
    public function testIsEqual($expected, array $match, array $against)
    {
        $this->assertEquals(
            $expected,
            $this->createTriggerCron($match)->isEqualTo(
                $this->createTriggerCron($against)
            )
        );
    }

    /**
     * @return array
     */
    public function equalityDataProvider()
    {
        $cases = [];

        $wd1 = (new WorkflowDefinition())->setName('wd1');
        $wd2 = (new WorkflowDefinition())->setName('wd2');

        $fieldsSets = [
            'cron' => ['1 * * * *', '* * * * *'],
            'filter' => ['w = 1', 'w = 2'],
            'queued' => [true, false],
            'workflowDefinition' => [$wd1, $wd2],
            'transitionName' => ['t1', 't2']
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
     * @return TransitionCronTrigger
     */
    protected function createTriggerCron(array $attributes)
    {
        $trigger = new TransitionCronTrigger();

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
        return new TransitionCronTrigger();
    }
}
