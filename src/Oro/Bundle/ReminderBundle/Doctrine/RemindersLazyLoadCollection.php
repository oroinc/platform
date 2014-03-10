<?php

namespace Oro\Bundle\ReminderBundle\Doctrine;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;

class RemindersLazyLoadCollection extends AbstractLazyCollection
{
    /**
     * @var ReminderRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var int
     */
    protected $identifiers;

    /**
     * @param ReminderRepository $repository
     * @param string $className
     * @param array $identifiers
     */
    public function __construct(ReminderRepository $repository, $className, array $identifiers)
    {
        $this->repository = $repository;
        $this->className = $className;
        $this->identifiers = $identifiers;
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitialize()
    {
        if (!$this->collection) {
            $reminders = $this->repository->findRemindersByEntity($this->className, current($this->identifiers));
            $this->collection = new ArrayCollection($reminders);
        }
    }
}
