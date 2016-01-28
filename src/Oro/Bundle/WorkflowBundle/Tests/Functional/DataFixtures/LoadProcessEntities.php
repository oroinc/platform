<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class LoadProcessEntities extends AbstractFixture
{
    const FIRST_DEFINITION = 'first';
    const SECOND_DEFINITION = 'second';
    const DISABLED_DEFINITION = 'disabled';
    const UPDATE_TRIGGER_FIELD = 'name';

    /** @var array */
    protected $definitions = [
        self::FIRST_DEFINITION => [
            'related_entity' => 'Test\Entity',
            'execution_order' => 10,
            'enabled' => true
        ],
        self::SECOND_DEFINITION => [
            'related_entity' => 'Test\Entity',
            'execution_order' => 20,
            'enabled' => true
        ],
        self::DISABLED_DEFINITION => [
            'related_entity' => 'Test\Entity',
            'execution_order' => 30,
            'enabled' => false
        ]
    ];

    /** @var array */
    protected $triggers = [
        'update' => [
            'definition' => self::FIRST_DEFINITION,
            'event' => ProcessTrigger::EVENT_UPDATE,
            'field' => self::UPDATE_TRIGGER_FIELD,
            'cron' => null
        ],
        'create' => [
            'definition' => self::SECOND_DEFINITION,
            'event' => ProcessTrigger::EVENT_CREATE,
            'field' => null,
            'cron' => null
        ],
        'delete' => [
            'definition' => self::SECOND_DEFINITION,
            'event' => ProcessTrigger::EVENT_DELETE,
            'field' => null,
            'cron' => null
        ],
        'cron' => [
            'definition' => self::SECOND_DEFINITION,
            'event' => null,
            'field' => null,
            'cron' => '*/1 * * * *'
        ],
        'create_disabled' => [
            'definition' => self::DISABLED_DEFINITION,
            'event' => ProcessTrigger::EVENT_CREATE,
            'field' => null,
            'cron' => null
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->definitions as $name => $config) {
            $definition = new ProcessDefinition();
            $definition
                ->setName($name)
                ->setLabel($name)
                ->setRelatedEntity($config['related_entity'])
                ->setExecutionOrder($config['execution_order'])
                ->setEnabled($config['enabled']);

            $this->definitions[$name] = $definition;

            $manager->persist($definition);
        }

        foreach ($this->triggers as $config) {
            $trigger = new ProcessTrigger();
            $trigger
                ->setDefinition($this->definitions[$config['definition']])
                ->setEvent($config['event'])
                ->setField($config['field'])
                ->setCron($config['cron']);

            $manager->persist($trigger);
        }

        $manager->flush();
    }
}
