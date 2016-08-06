<?php

namespace Oro\Bundle\SecurityBundle\Layout\DataProvider;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

class AclProvider extends AbstractServerRenderDataProvider
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param SecurityFacade  $securityFacade
     * @param ManagerRegistry $doctrine
     */
    public function __construct(SecurityFacade $securityFacade, ManagerRegistry $doctrine)
    {
        $this->securityFacade = $securityFacade;
        $this->doctrine = $doctrine;
    }

    /**
     * @param string|string[] $attributes
     * @param mixed           $object
     * @return bool
     */
    public function isGranted($attributes, $object = null)
    {
        if (!$this->securityFacade->hasLoggedUser()) {
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

        return $this->securityFacade->isGranted($attributes, $object);
    }
}
