<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\UserBundle\Entity\User;

class ApplicationsHelper implements ApplicationsHelperInterface
{
    use ApplicationsHelperTrait;

    const DEFAULT_APPLICATION = 'default';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var string */
    protected $currentApplication = false;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentApplication()
    {
        $token = $this->tokenStorage->getToken();

        return $token && $token->getUser() instanceof User ? self::DEFAULT_APPLICATION : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetRoute()
    {
        return 'oro_action_widget_buttons';
    }

    /**
     * {@inheritdoc}
     */
    public function getDialogRoute()
    {
        return 'oro_action_widget_form';
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionRoute()
    {
        return 'oro_action_operation_execute';
    }
}
