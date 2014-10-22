<?php

namespace Oro\Bundle\IntegrationBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

/**
 * actions:
 *    - @assign_constant_value:
 *         attribute: $.localType
 *         value: Oro\Bundle\IntegrationBundle\Entity\ChangeSet::TYPE_LOCAL
 *    - @save_change_set:
 *        data: $.data
 *        changeSet: $.changeSet
 *        type: $.localType
 */
class SaveChangeSetAction extends AbstractChangeSetAction
{
    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_CHANGESET])) {
            throw new InvalidParameterException('ChangeSet parameter is required');
        }

        parent::initialize($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $changeSet = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_CHANGESET]);
        if (!$changeSet) {
            return;
        }

        $entity    = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_DATA]);
        $type      = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_TYPE]);

        $this->changeSetManager->setChanges($entity, $type, array_keys($changeSet));
    }
}
