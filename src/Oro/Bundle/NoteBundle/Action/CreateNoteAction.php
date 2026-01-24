<?php

namespace Oro\Bundle\NoteBundle\Action;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Workflow action that creates a {@see Note} and associates it with a target entity.
 *
 * This action can be used in workflows and automation rules to programmatically create
 * notes. It accepts a message, target entity, and optional attribute name for storing
 * the created note. The action creates a new {@see Note} entity, sets its message and timestamp,
 * associates it with the target entity through the activity manager, and persists it to
 * the database. The created note can optionally be stored in a workflow context attribute
 * for further processing.
 */
class CreateNoteAction extends AbstractAction
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var array|null */
    protected $options;

    public function __construct(
        ManagerRegistry $registry,
        ActivityManager $activityManager,
        ContextAccessor $contextAccessor
    ) {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
        $this->activityManager = $activityManager;
    }

    #[\Override]
    public function initialize(array $options)
    {
        $argsCount = count($options);
        if (($argsCount < 2) || ($argsCount > 3)) {
            throw new InvalidParameterException('Two or three parameters are required.');
        }

        $this->options = [];

        if (isset($options['message'])) {
            $this->options['message'] = $options['message'];
        } elseif (isset($options[0])) {
            $this->options['message'] = $options[0];
        } else {
            throw new InvalidParameterException('Parameter "message" has to be set.');
        }

        if (isset($options['target_entity'])) {
            $this->options['target_entity'] = $options['target_entity'];
        } elseif (isset($options[1])) {
            $this->options['target_entity'] = $options[1];
        } else {
            throw new InvalidParameterException('Parameter "target_entity" has to be set.');
        }

        if (isset($options['attribute'])) {
            $this->options['attribute'] = $options['attribute'];
        } elseif (isset($options[2])) {
            $this->options['attribute'] = $options[2];
        }
    }

    #[\Override]
    protected function executeAction($context)
    {
        $message = $this->contextAccessor->getValue($context, $this->options['message']);
        $targetEntity = $this->contextAccessor->getValue($context, $this->options['target_entity']);

        $note = new Note();
        $note->setMessage($message);
        $note->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $this->activityManager->setActivityTargets($note, [$targetEntity]);

        $em = $this->getEntityManager();
        $em->persist($note);
        $em->flush();

        if (isset($this->options['attribute'])) {
            $this->contextAccessor->setValue($context, $this->options['attribute'], $note);
        }
    }

    private function getEntityManager(): ObjectManager
    {
        return $this->registry->getManagerForClass(Note::class);
    }
}
