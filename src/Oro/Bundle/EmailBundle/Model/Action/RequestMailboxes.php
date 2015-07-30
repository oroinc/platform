<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

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

    /**
     * @param ContextAccessor $contextAccessor
     * @param Registry        $doctrine
     */
    public function __construct(ContextAccessor $contextAccessor, Registry $doctrine)
    {
        parent::__construct($contextAccessor);
        $this->doctrine = $doctrine;
    }

    /**
     * @param mixed $context
     */
    protected function executeAction($context)
    {
        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();

        $type = $this->contextAccessor->getValue($context, $this->processType);

        $qb = $manager->createQueryBuilder();
        $qb->select('mb')
            ->from('OroEmailBundle:Mailbox', 'mb')
            ->join('mb.emailUsers', 'eu')
            ->leftJoin('mb.processSettings', 'ps')
            ->where($qb->expr()->isInstanceOf('ps', $type))
            ->andWhere('eu.email = :email');

        $qb->setParameters([
            'email' => $this->contextAccessor->getValue($context, $this->email),
        ]);

        $results = $qb->getQuery()->getResult();

        $this->contextAccessor->setValue($context, $this->attribute, $results);
    }

    /**
     * Initialize action based on passed options.
     *
     * @param array $options
     *
     * @return ActionInterface
     * @throws InvalidParameterException
     */
    public function initialize(array $options)
    {
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
