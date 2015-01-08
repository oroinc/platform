<?php

namespace Oro\Bundle\IntegrationBundle\Model\Action;

/**
 * actions:
 *    - @remove_fields_changes:
 *        entity: $.data
 */
class RemoveFieldsChangesAction extends AbstractFieldsChangesAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $entity = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_ENTITY]);

        $this->fieldsChangesManager->removeChanges($entity);
    }
}
