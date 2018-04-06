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

    const TRIGGER_UPDATE = 'trigger_update';
    const TRIGGER_CREATE = 'trigger_create';
    const TRIGGER_DELETE = 'trigger_delete';
    const TRIGGER_CRON = 'trigger_cron';
    const TRIGGER_DISABLED = 'trigger_disabled';

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
        self::TRIGGER_UPDATE => [
            'definition' => self::FIRST_DEFINITION,
            'event' => ProcessTrigger::EVENT_UPDATE,
            'field' => self::UPDATE_TRIGGER_FIELD,
            'cron' => null
        ],
        self::TRIGGER_CREATE => [
            'definition' => self::SECOND_DEFINITION,
            'event' => ProcessTrigger::EVENT_CREATE,
            'field' => null,
            'cron' => null
        ],
        self::TRIGGER_DELETE => [
            'definition' => self::SECOND_DEFINITION,
            'event' => ProcessTrigger::EVENT_DELETE,
            'field' => null,
            'cron' => null
        ],
        self::TRIGGER_CRON => [
            'definition' => self::SECOND_DEFINITION,
            'event' => null,
            'field' => null,
            'cron' => '*/1 * * * *'
        ],
        self::TRIGGER_DISABLED => [
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

        foreach ($this->triggers as $key => $config) {
            $trigger = new ProcessTrigger();
            $trigger
                ->setDefinition($this->definitions[$config['definition']])
                ->setEvent($config['event'])
                ->setField($config['field'])
                ->setCron($config['cron']);

            $manager->persist($trigger);
            $this->addReference($key, $trigger);
        }

        $manager->flush();
    }
}
