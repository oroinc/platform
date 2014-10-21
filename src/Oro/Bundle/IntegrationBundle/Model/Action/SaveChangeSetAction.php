<?php

namespace Oro\Bundle\IntegrationBundle\Model\Action;

use Oro\Bundle\IntegrationBundle\Entity\ChangeSet;
use Oro\Bundle\IntegrationBundle\Manager\ChangeSetManager;
use Oro\Bundle\WorkflowBundle\Model\AbstractStorage;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;

class SaveChangeSetAction extends AbstractAction
{
    /**
     * @var ChangeSetManager
     */
    protected $changeSetManager;

    /**
     * @param ChangeSetManager $changeSetManager
     */
    public function setChangeSetManager(ChangeSetManager $changeSetManager)
    {
        $this->changeSetManager = $changeSetManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (!$context instanceof AbstractStorage) {
            return;
        }

        if (!$context->has('changeSet')) {
            return;
        }

        $changeSet = $context->get('changeSet');
        if (!$changeSet) {
            return;
        }

        $fields = array_keys($changeSet);
        $entity = $context->get('data');
        $this->changeSetManager->setChanges($entity, ChangeSet::TYPE_LOCAL, $fields);
    }
}
