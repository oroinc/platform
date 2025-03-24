<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TransitionCronTriggerTest extends AbstractTransitionTriggerTestCase
{
    #[\Override]
    protected function getEntity(): BaseTransitionTrigger
    {
        return new TransitionCronTrigger();
    }

    private function createTriggerCron(array $attributes): TransitionCronTrigger
    {
        $trigger = new TransitionCronTrigger();
        foreach ($attributes as $name => $value) {
            $method = 'set' . ucfirst($name);
            $trigger->$method($value);
        }

        return $trigger;
    }

    #[\Override]
    public function testAccessors(): void
    {
        parent::testAccessors();

        self::assertPropertyAccessors($this->entity, [
            ['cron', 'test_cron'],
            ['filter', 'test_filter'],
        ]);
    }

    public function testImport(): void
    {
        /** @var TransitionCronTrigger $trigger */
        $trigger = $this->getEntity();
        /** @var TransitionCronTrigger $entity */
        $entity = $this->entity;
        $this->setDataToTrigger($trigger);
        $trigger->setCron('test_cron');
        $trigger->setFilter('test_filter');
        $entity->import($trigger);
        $this->assertImportData();
        self::assertEquals($trigger->getCron(), $entity->getCron());
        self::assertEquals($trigger->getFilter(), $entity->getFilter());
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(array $data, string $expected): void
    {
        self::assertEquals($expected, (string)$this->createTriggerCron($data));
    }

    public function toStringDataProvider(): array
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
     */
    public function testIsEqual(bool $expected, array $match, array $against): void
    {
        self::assertEquals(
            $expected,
            $this->createTriggerCron($match)->isEqualTo(
                $this->createTriggerCron($against)
            )
        );
    }

    public function equalityDataProvider(): array
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
            [$first, $second] = $values;
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

        return array_merge($cases, $oneDiffers);
    }
}
