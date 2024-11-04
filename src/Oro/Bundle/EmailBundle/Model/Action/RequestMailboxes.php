<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * The action to request/find mailboxes.
 */
class RequestMailboxes extends AbstractAction
{
    private ManagerRegistry $doctrine;
    private MailboxProcessStorage $processStorage;

    /** @var string */
    private $attribute;

    /** @var string */
    private $processType;

    /** @var string */
    private $email;

    public function __construct(
        ContextAccessor $contextAccessor,
        ManagerRegistry $doctrine,
        MailboxProcessStorage $processStorage
    ) {
        parent::__construct($contextAccessor);
        $this->doctrine = $doctrine;
        $this->processStorage = $processStorage;
    }

    #[\Override]
    protected function executeAction($context)
    {
        $settingsClass = $this->contextAccessor->getValue($context, $this->processType);
        $settingsClass = $this->processStorage->getProcess($settingsClass)->getSettingsEntityFQCN();

        $email = $this->contextAccessor->getValue($context, $this->email);
        $results = [];
        if ($email) {
            $results = $this->doctrine->getRepository(Mailbox::class)
                ->findBySettingsClassAndEmail($settingsClass, $email);
        }

        $this->contextAccessor->setValue($context, $this->attribute, $results);
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (count($options) !== 3) {
            throw new InvalidParameterException('Three options must be defined.');
        }

        if (!isset($options['attribute']) && !isset($options[0])) {
            throw new InvalidParameterException('Attribute must be defined.');
        }

        if (!isset($options['process_type']) && !isset($options[1])) {
            throw new InvalidParameterException('Process type must be defined.');
        }

        if (!isset($options['email']) && !isset($options[2])) {
            throw new InvalidParameterException('Email must be defined.');
        }

        $this->attribute = $options['attribute'] ?? $options[0];
        $this->processType = $options['process_type'] ?? $options[1];
        $this->email = $options['email'] ?? $options[2];
    }
}
