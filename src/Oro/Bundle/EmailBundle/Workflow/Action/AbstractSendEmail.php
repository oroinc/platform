<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

abstract class AbstractSendEmail extends AbstractAction
{
    /**
     * @var EmailAddressHelper
     */
    protected $emailAddressHelper;

    /**
     * @var NameFormatter
     */
    protected $nameFormatter;

    /**
     * @param ContextAccessor    $contextAccessor
     * @param EmailAddressHelper $emailAddressHelper
     * @param NameFormatter      $nameFormatter
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        EmailAddressHelper $emailAddressHelper,
        NameFormatter $nameFormatter
    ) {
        parent::__construct($contextAccessor);

        $this->emailAddressHelper = $emailAddressHelper;
        $this->nameFormatter      = $nameFormatter;
    }

    /**
     * @param $option
     *
     * @throws InvalidParameterException
     */
    protected function assertEmailAddressOption($option)
    {
        if (is_array($option) && array_key_exists('name', $option) && !array_key_exists('email', $option)) {
            throw new InvalidParameterException('Email parameter is required');
        }
    }

    /**
     * Get email address prepared for sending.
     *
     * @param mixed $context
     * @param string|array $data
     *
     * @return string
     */
    protected function getEmailAddress($context, $data)
    {
        $name = null;
        $emailAddress = $this->contextAccessor->getValue($context, $data);

        if (is_array($data)) {
            $emailAddress = $this->contextAccessor->getValue($context, $data['email']);

            if (array_key_exists('name', $data)) {
                $data['name'] = $this->contextAccessor->getValue($context, $data['name']);

                if (is_object($data['name'])) {
                    $name = $this->nameFormatter->format($data['name']);
                } else {
                    $name = $data['name'];
                }
            }
        }

        return $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $name);
    }
}
