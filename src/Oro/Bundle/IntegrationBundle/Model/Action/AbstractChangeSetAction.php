<?php

namespace Oro\Bundle\IntegrationBundle\Model\Action;

use Oro\Bundle\IntegrationBundle\Manager\ChangeSetManager;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;

abstract class AbstractChangeSetAction extends AbstractAction
{
    const OPTION_KEY_DATA = 'data';
    const OPTION_KEY_CHANGESET = 'changeSet';
    const OPTION_KEY_TYPE = 'type';

    /**
     * @var array
     */
    protected $options = [];

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
        if (empty($options[self::OPTION_KEY_DATA])) {
            throw new InvalidParameterException('Data parameter is required');
        }

        if (empty($options[self::OPTION_KEY_TYPE])) {
            throw new InvalidParameterException('Type parameter is required');
        }

        $this->options = $options;

        return $this;
    }
}
