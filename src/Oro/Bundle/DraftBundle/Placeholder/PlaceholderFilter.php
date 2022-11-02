<?php

namespace Oro\Bundle\DraftBundle\Placeholder;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Responsible for verifying access for entity
 */
class PlaceholderFilter
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function isApplicable(DraftableInterface $entity): bool
    {
        return $this->authorizationChecker->isGranted('CREATE_DRAFT', $entity);
    }
}
