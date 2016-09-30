<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class LoadTransitionTriggers extends AbstractFixture implements DependentFixtureInterface
{
    const UPDATE_TRIGGER_FIELD = 'field_name';

    const TRIGGER_CREATE = 'trigger_create';
    const TRIGGER_UPDATE = 'trigger_update';
    const TRIGGER_DELETE = 'trigger_delete';
    const TRIGGER_CRON = 'trigger_cron';
    const TRIGGER_DISABLED = 'trigger_disabled';

    /** @var array */
    protected static $triggers = [
        self::TRIGGER_CREATE => [
            'active' => true,
            'class' => TransitionEventTrigger::class,
            'definition' => LoadWorkflowDefinitions::WITH_GROUPS1,
            'transition_name' => 'starting_point_transition',
            'event' => EventTriggerInterface::EVENT_CREATE,
            'field' => null
        ],
        self::TRIGGER_UPDATE => [
            'active' => true,
            'class' => TransitionEventTrigger::class,
            'definition' => LoadWorkflowDefinitions::WITH_GROUPS1,
            'transition_name' => 'starting_point_transition',
            'event' => EventTriggerInterface::EVENT_UPDATE,
            'field' => self::UPDATE_TRIGGER_FIELD
        ],
        self::TRIGGER_DELETE => [
            'active' => true,
            'class' => TransitionEventTrigger::class,
            'definition' => LoadWorkflowDefinitions::WITH_GROUPS1,
            'transition_name' => 'starting_point_transition',
            'event' => EventTriggerInterface::EVENT_DELETE,
            'field' => null
        ],
        self::TRIGGER_CRON => [
            'active' => true,
            'class' => TransitionCronTrigger::class,
            'definition' => LoadWorkflowDefinitions::WITH_GROUPS1,
            'transition_name' => 'second_point_transition',
            'cron' => '*/1 * * * *'
        ],
        self::TRIGGER_DISABLED => [
            'active' => false,
            'class' => TransitionEventTrigger::class,
            'definition' => LoadWorkflowDefinitions::WITH_GROUPS2,
            'transition_name' => 'starting_point_transition',
            'event' => EventTriggerInterface::EVENT_CREATE,
            'field' => null
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadWorkflowDefinitions::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$triggers as $key => $config) {
            /** @var WorkflowDefinition $definition */
            $definition = $this->getReference('workflow.' . $config['definition']);
            $definition->setActive($config['active']);

            $className = $config['class'];

            /** @var BaseTransitionTrigger $trigger */
            $trigger = new $className();
            $trigger->setWorkflowDefinition($definition)
                ->setTransitionName($config['transition_name'])
                ->setQueued(false);

            if ($trigger instanceof TransitionEventTrigger) {
                $trigger->setEvent($config['event'])->setField($config['field']);
            } elseif ($trigger instanceof TransitionCronTrigger) {
                $trigger->setCron($config['cron']);
            }

            $manager->persist($trigger);
            $this->addReference($key, $trigger);
        }

        $manager->flush();
    }
}
