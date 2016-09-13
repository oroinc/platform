<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;

class TransitionTriggerCronTest extends AbstractTransitionTriggerTestCase
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
        /** @var TransitionTriggerCron $entity */
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
     * @dataProvider equalityData
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
     * @param array $attributes
     * @return TransitionTriggerCron
     */
    protected function createTriggerCron(array $attributes)
    {
        $trigger = new TransitionTriggerCron();

        foreach ($attributes as $name => $value) {
            $method = 'set' . ucfirst($name);
            $trigger->$method($value);
        }

        return $trigger;
    }

    /**
     * @return array
     */
    public function equalityData()
    {
        $cases = [];

        $fieldsSets = [
            'cron' => ['1 * * * *', '* * * * *'],
            'filter' => ['w = 1', 'w = 2']
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
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return new TransitionTriggerCron();
    }
}
