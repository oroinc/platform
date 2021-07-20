<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Filter;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Component\Duplicator\Filter\Filter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Responsible for updating  draft owner field.
 */
class OwnerFilter implements Filter
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * OwnerFilter constructor.
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param DraftableInterface $object
     * @param string $property
     * @param callable $objectCopier
     */
    public function apply($object, $property, $objectCopier): void
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $object->setDraftOwner($user);
    }
}
