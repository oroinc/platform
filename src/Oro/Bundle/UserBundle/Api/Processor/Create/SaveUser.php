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
class SaveUser implements ProcessorInterface
{
    /** @var UserManager */
    private $userManager;

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

        // generate random secure password for a user
        if (!$user->getPlainPassword()) {
            $user->setPlainPassword($this->userManager->generatePassword());
        }

        $this->userManager->updateUser($user);
    }
}
