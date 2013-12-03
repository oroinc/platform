<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class SendEmail extends AbstractAction
{
    /**
     * @var Processor
     */
    protected $emailProcessor;

    public function __construct(ContextAccessor $contextAccessor, Processor $emailProcessor)
    {
        parent::__construct($contextAccessor);
        $this->emailProcessor = $emailProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {

    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $email = $this->getInitializedEmailModel();
        $this->emailProcessor->process($email);
    }

    protected function getInitializedEmailModel()
    {
        return new Email();
    }
}
