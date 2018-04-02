<?php

namespace Oro\Bundle\UserBundle\Event;

use Oro\Bundle\UserBundle\Entity\Impersonation;
use Symfony\Component\EventDispatcher\Event;

/**
 * Triggers on successful impersonation login
 */
class ImpersonationSuccessEvent extends Event
{
    const EVENT_NAME = 'oro_user.impersonation_success';

    /**
     * @var Impersonation
     */
    protected $impersonation;

    /**
     * @param Impersonation $impersonation
     */
    public function __construct(Impersonation $impersonation)
    {
        $this->impersonation = $impersonation;
    }

    /**
     * @return Impersonation
     */
    public function getImpersonation()
    {
        return $this->impersonation;
    }
}
