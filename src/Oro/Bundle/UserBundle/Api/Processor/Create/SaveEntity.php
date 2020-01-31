<?php

namespace Oro\Bundle\UserBundle\Api\Processor\Create;

use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves new User entity to the database.
 * @deprecated replaced with Oro\Bundle\UserBundle\Api\Processor\UpdateNewUser
 */
class SaveEntity implements ProcessorInterface
{
    /** @var UserManager */
    protected $userManager;

    /**
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
    }
}
