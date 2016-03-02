<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Model\ContextAccessor;

abstract class AbstractSendEmail extends AbstractAction implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Processor
     */
    protected $emailProcessor;

    /**
     * @var EmailAddressHelper
     */
    protected $emailAddressHelper;

    /**
     * @var EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @param ContextAccessor    $contextAccessor
     * @param Processor          $emailProcessor
     * @param EmailAddressHelper $emailAddressHelper
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        Processor $emailProcessor,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver
    ) {
        parent::__construct($contextAccessor);

        $this->emailProcessor     = $emailProcessor;
        $this->emailAddressHelper = $emailAddressHelper;
        $this->entityNameResolver = $entityNameResolver;
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
                    $name = $this->entityNameResolver->getName($data['name']);
                } else {
                    $name = $data['name'];
                }
            }
        }

        return $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $name);
    }
}
