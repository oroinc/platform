<?php

namespace Oro\Bundle\IntegrationBundle\Model\Action;

use Oro\Bundle\IntegrationBundle\Manager\FieldsChangesManager;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;

abstract class AbstractFieldsChangesAction extends AbstractAction
{
    const OPTION_KEY_ENTITY = 'entity';
    const OPTION_KEY_CHANGESET = 'changeSet';

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var FieldsChangesManager
     */
    protected $fieldsChangesManager;

    /**
     * @param FieldsChangesManager $fieldsChangesManager
     */
    public function setFieldsChangesManager(FieldsChangesManager $fieldsChangesManager)
    {
        $this->fieldsChangesManager = $fieldsChangesManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_ENTITY])) {
            throw new InvalidParameterException('Entity parameter is required');
        }

        $this->options = $options;

        return $this;
    }
}
