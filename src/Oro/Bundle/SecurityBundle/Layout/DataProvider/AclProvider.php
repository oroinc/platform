<?php

namespace Oro\Bundle\SecurityBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AclProvider
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param ManagerRegistry               $doctrine
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        ManagerRegistry $doctrine
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->doctrine = $doctrine;
    }

    /**
     * @param string|string[] $attributes
     * @param mixed           $object
     * @return bool
     */
    public function isGranted($attributes, $object = null)
    {
        if (!$this->tokenAccessor->hasUser()) {
            return false;
        }

        if (is_object($object)) {
            $class = ClassUtils::getRealClass($object);
            $objectManager = $this->doctrine->getManagerForClass($class);
            if ($objectManager instanceof EntityManager) {
                $unitOfWork = $objectManager->getUnitOfWork();
                if ($unitOfWork->isScheduledForInsert($object) || !$unitOfWork->isInIdentityMap($object)) {
                    $object = 'entity:'.$class;
                }
            }
        }

        return $this->authorizationChecker->isGranted($attributes, $object);
    }
}
