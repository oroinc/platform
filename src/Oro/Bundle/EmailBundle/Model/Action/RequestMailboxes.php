<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

class RequestMailboxes extends AbstractAction
{
    /** @var string */
    protected $attribute;

    /** @var string */
    protected $processType;

    /** @var string */
    protected $email;

    /** @var Registry */
    private $doctrine;

    /** @var MailboxProcessStorage */
    private $processStorage;

    /**
     * @param ContextAccessor       $contextAccessor
     * @param Registry              $doctrine
     * @param MailboxProcessStorage $processStorage
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        Registry $doctrine,
        MailboxProcessStorage $processStorage
    ) {
        parent::__construct($contextAccessor);
        $this->doctrine = $doctrine;
        $this->processStorage = $processStorage;
    }

    /**
     * @param mixed $context
     */
    protected function executeAction($context)
    {
        $settingsClass = $this->contextAccessor->getValue($context, $this->processType);
        $settingsClass = $this->processStorage->getProcess($settingsClass)->getSettingsEntityFQCN();

        $email = $this->contextAccessor->getValue($context, $this->email);
        $results = [];
        if ($email) {
            $results = $this->doctrine->getRepository('OroEmailBundle:Mailbox')
                ->findBySettingsClassAndEmail($settingsClass, $email);
        }

        $this->contextAccessor->setValue($context, $this->attribute, $results);
    }

    /**
     * Initialize action based on passed options.
     *
     * @param array $options
     *
     * @return ActionInterface
     * @throws InvalidParameterException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
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

        $this->attribute   = isset($options['attribute'])    ? $options['attribute']    : $options[0];
        $this->processType = isset($options['process_type']) ? $options['process_type'] : $options[1];
        $this->email       = isset($options['email'])        ? $options['email']        : $options[2];
    }
}
