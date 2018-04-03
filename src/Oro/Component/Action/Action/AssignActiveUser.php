<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\ActionException;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Assigns|Return active user (current logged in system) action
 */
class AssignActiveUser extends AbstractAction
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var array */
    protected $options;

    /**
     * @param ContextAccessor       $contextAccessor
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ContextAccessor $contextAccessor, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($contextAccessor);

        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $activeUser = null;

        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $activeUser = $token->getUser();
        }

        if (!$activeUser && $this->options['exceptionOnNotFound']) {
            throw new ActionException('Can\'t extract active user');
        }

        $this->contextAccessor->setValue($context, $this->options['attribute'], $activeUser);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!in_array(count($options), [1, 2])) {
            throw new InvalidParameterException('Only one or two attribute parameters must be defined');
        }

        if (isset($options[0])) {
            $options['attribute'] = $options[0];
            unset($options[0]);
        }

        if (isset($options[1])) {
            $options['exceptionOnNotFound'] = (bool)$options[1];
        }
        if (!isset($options['exceptionOnNotFound'])) {
            $options['exceptionOnNotFound'] = true;
        }

        if (!isset($options['attribute'])) {
            throw new InvalidParameterException('Attribute must be defined');
        }
        if (!$options['attribute'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }

        $this->options = $options;

        return $this;
    }
}
