<?php

namespace Oro\Bundle\UserBundle\Api\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves new User entity to the database.
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
        /** @var CreateContext $context */

        /** @var User $user */
        $user = $context->getResult();
        if (!is_object($user)) {
            // entity does not exist
            return;
        }

        // Generate random secure password for user.
        if (!$user->getPassword()) {
            $user->setPlainPassword($this->userManager->generatePassword());
        }

        $this->userManager->updateUser($user);
    }
}
