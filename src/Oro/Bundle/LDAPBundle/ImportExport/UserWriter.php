<?php
namespace Oro\Bundle\LDAPBundle\ImportExport;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\EntityDetachFixer;
use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;
use Oro\Bundle\LDAPBundle\EventListener\UserChangeListener;

class UserWriter extends EntityWriter
{
    /** @var UserChangeListener */
    private $listener;

    /**
     * @param EntityManager $entityManager
     * @param EntityDetachFixer $detachFixer
     * @param ContextRegistry $contextRegistry
     * @param UserChangeListener $listener
     */
    public function __construct(
        EntityManager $entityManager,
        EntityDetachFixer $detachFixer,
        ContextRegistry $contextRegistry,
        UserChangeListener $listener
    ) {
        parent::__construct($entityManager, $detachFixer, $contextRegistry);
        $this->listener = $listener;
    }

    /**
     * Initializes user writer.
     * Removes UserChangeListener, so it won't trigger for each imported user.
     */
    public function initialize()
    {
        $eventManager = $this->entityManager->getEventManager();
        $eventManager->removeEventListener(['onFlush', 'postFlush'], $this->listener);
    }
}
