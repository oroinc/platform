<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\UserBundle\Entity\User;

class ApplicationsHelper
{
    const DEFAULT_APPLICATION = 'default';

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Operation $operation
     * @return bool
     */
    public function isApplicationsValid(Operation $operation)
    {
        $applications = $operation->getDefinition()->getApplications();
        if (empty($applications)) {
            return true;
        }

        return in_array($this->getCurrentApplication(), $applications, true);
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
