<?php

namespace Oro\Bundle\SecurityBundle\Request\ParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Doctrine\Common\Persistence\ManagerRegistry;

use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter as BaseParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Class DoctrineParamConverter
 * @package Oro\Bundle\SecurityBundle\Request\ParamConverter
 */
class DoctrineParamConverter extends BaseParamConverter
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param ManagerRegistry     $registry
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(
        ManagerRegistry $registry = null,
        SecurityFacade $securityFacade = null
    ) {
        parent::__construct($registry);
        $this->securityFacade      = $securityFacade;
    }

    /**
     * Stores the object in the request.
     *
     * @param Request                $request
     * @param ConfigurationInterface $configuration
     * @return bool
     * @throws AccessDeniedException When User doesn't have permission to the object
     */
    public function apply(Request $request, ConfigurationInterface $configuration)
    {
        $request->attributes->set('_oro_access_checked', false);
        $isSet = parent::apply($request, $configuration);

        if ($this->securityFacade && $isSet) {
            $object = $request->attributes->get($configuration->getName());
            if ($object) {
                $granted = $this->securityFacade->isRequestObjectIsGranted($request, $object);
                if ($granted === -1) {
                    $acl = $this->securityFacade->getRequestAcl($request);
                    throw new AccessDeniedException(
                        'You do not get ' . $acl->getPermission() . ' permission for this object'
                    );
                } elseif ($granted === 1) {
                    $request->attributes->set('_oro_access_checked', true);
                }
            }
        }

        return $isSet;
    }
}
