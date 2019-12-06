<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Filter;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Component\Duplicator\Filter\Filter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Responsible for updating  draft owner field.
 */
class OwnerFilter implements Filter
{
    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * OwnerFilter constructor.
     *
     * @param TokenStorage $tokenStorage
     */
    public function __construct(TokenStorage $tokenStorage)
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
