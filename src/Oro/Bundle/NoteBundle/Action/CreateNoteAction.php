<?php

namespace Oro\Bundle\NoteBundle\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

class CreateNoteAction extends AbstractAction
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var array|null */
    protected $options;

    /**
     * @param ManagerRegistry $registry
     * @param ActivityManager $activityManager
     * @param ContextAccessor $contextAccessor
     */
    public function __construct(
        ManagerRegistry $registry,
        ActivityManager $activityManager,
        ContextAccessor $contextAccessor
    ) {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
        $this->activityManager = $activityManager;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * @return ObjectManager
     */
    private function getEntityManager(): ObjectManager
    {
        return $this->registry->getManagerForClass(Note::class);
    }
}
