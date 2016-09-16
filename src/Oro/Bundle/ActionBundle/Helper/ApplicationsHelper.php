<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\UserBundle\Entity\User;

class ApplicationsHelper
{
    const DEFAULT_APPLICATION = 'default';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var string */
    protected $currentApplication;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param array $applications
     * @return bool
     */
    public function isApplicationsValid(array $applications)
    {
        if (empty($applications)) {
            return true;
        }

        if (null === $this->currentApplication) {
            $this->currentApplication = $this->getCurrentApplication();
        }

        return in_array($this->currentApplication, $applications, true);
    }

    /**
     * @return string|null
     */
    public function getCurrentApplication()
    {
        $token = $this->tokenStorage->getToken();

        return $token && $token->getUser() instanceof User ? self::DEFAULT_APPLICATION : null;
    }

    /**
     * @return string
     */
    public function getWidgetRoute()
    {
        return 'oro_action_widget_buttons';
    }

    /**
     * @return string
     */
    public function getDialogRoute()
    {
        return 'oro_action_widget_form';
    }

    /**
     * @return string
     */
    public function getExecutionRoute()
    {
        return 'oro_action_operation_execute';
    }
}
