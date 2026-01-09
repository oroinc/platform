<?php

namespace Oro\Bundle\IntegrationBundle\Model\Action;

use Oro\Bundle\IntegrationBundle\Manager\FieldsChangesManager;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;

/**
 * Provides common functionality for actions that track entity field changes.
 *
 * This base class integrates with the {@see FieldsChangesManager} to handle field change tracking
 * for integration-related entities.
 * Subclasses should implement specific actions that either save or retrieve field change information.
 */
abstract class AbstractFieldsChangesAction extends AbstractAction
{
    public const OPTION_KEY_ENTITY = 'entity';
    public const OPTION_KEY_CHANGESET = 'changeSet';

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var FieldsChangesManager
     */
    protected $fieldsChangesManager;

    public function setFieldsChangesManager(FieldsChangesManager $fieldsChangesManager)
    {
        $this->fieldsChangesManager = $fieldsChangesManager;
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_ENTITY])) {
            throw new InvalidParameterException('Entity parameter is required');
        }

        $this->options = $options;

        return $this;
    }
}
