<?php


namespace Oro\Bundle\UserBundle\Api\Processor\Get;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Adds an identifier of the current logged in user to the context.
 */
class SetUserProfileEntityId implements ProcessorInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

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
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof AbstractUser) {
                $context->setId($user->getId());
            }
        }
    }
}
