<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

class AssignActiveUser extends AbstractAction
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param ContextAccessor $contextAccessor
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(ContextAccessor $contextAccessor, SecurityContextInterface $securityContext)
    {
        parent::__construct($contextAccessor);

        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $activeUser = null;

        if ($token = $this->securityContext->getToken()) {
            $activeUser = $token->getUser();
        }

        if (!$activeUser) {
            throw new \LogicException('Can\'t extract active user');
        }

        $this->contextAccessor->setValue($context, $this->options['attribute'], $activeUser);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (count($options) != 1) {
            throw new InvalidParameterException('Only one attribute parameter must be defined');
        }

        if (isset($options[0])) {
            $options['attribute'] = $options[0];
            unset($options[0]);
        }

        if (!isset($options['attribute'])) {
            throw new InvalidParameterException('Attribute must be defined');
        }
        if (!$options['attribute'] instanceof PropertyPath) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }

        $this->options = $options;

        return $this;
    }
}
