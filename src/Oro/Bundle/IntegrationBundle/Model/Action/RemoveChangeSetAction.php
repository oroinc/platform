<?php

namespace Oro\Bundle\IntegrationBundle\Model\Action;

/**
 * actions:
 *    - @assign_constant_value:
 *         attribute: $.remoteType
 *         value: Oro\Bundle\IntegrationBundle\Entity\ChangeSet::TYPE_REMOTE
 *    - @remove_change_set:
 *        data: $.data
 *        type: %oro_integration.change_set.class%::TYPE_REMOTE
 */
class RemoveChangeSetAction extends AbstractChangeSetAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $entity = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_DATA]);
        $type   = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_TYPE]);

        $this->changeSetManager->removeChanges($entity, $type);
    }
}
