<?php

namespace Oro\Bundle\IntegrationBundle\Model\Action;

use Oro\Component\Action\Exception\InvalidParameterException;

/**
 * actions:
 *    - @save_fields_changes:
 *        entity: $.data
 *        changeSet: $.changeSet
 */
class SaveFieldsChangesAction extends AbstractFieldsChangesAction
{
    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_CHANGESET])) {
            throw new InvalidParameterException('changeSet parameter is required');
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

        $entity = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_ENTITY]);

        $this->fieldsChangesManager->setChanges($entity, array_keys($changeSet));
    }
}
