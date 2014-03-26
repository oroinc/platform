<?php

namespace Oro\Bundle\SecurityBundle\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter as BaseParamConverter;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

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
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @param ManagerRegistry     $registry
     * @param SecurityFacade      $securityFacade
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(
        ManagerRegistry $registry = null,
        SecurityFacade $securityFacade = null,
        EntityClassResolver $entityClassResolver = null
    ) {
        parent::__construct($registry);
        $this->securityFacade      = $securityFacade;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * Stores the object in the request.
     *
     * @param Request                $request
     * @param ConfigurationInterface $configuration
     * @return bool
     * @throws AccessDeniedException When User doesn't have permission to the object
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function apply(Request $request, ConfigurationInterface $configuration)
    {
        $request->attributes->set('_oro_access_checked', false);
        $isSet = parent::apply($request, $configuration);

        if ($this->securityFacade && $this->entityClassResolver && $isSet) {
            $object     = $request->attributes->get($configuration->getName());
            $controller = $request->attributes->get('_controller');
            if ($object && strpos($controller, '::') !== false) {
                $controllerData = explode('::', $controller);
                $aclAnnotation  = $this->securityFacade->getClassMethodAnnotation(
                    $controllerData[0],
                    $controllerData[1]
                );
                if ($aclAnnotation) {
                    $class      = $aclAnnotation->getClass();
                    $permission = $aclAnnotation->getPermission();

                    if ($permission
                        && $class
                        && $this->entityClassResolver->isEntity($class)
                        && is_a($object, $this->entityClassResolver->getEntityClass($class))
                    ) {
                        if (!$this->securityFacade->isGranted($permission, $object)) {
                            throw new AccessDeniedException(
                                'You do not get ' . $permission . ' permission for this object'
                            );
                        } else {
                            $request->attributes->set('_oro_access_checked', true);
                        }
                    }
                }
            }
        }

        return $isSet;
    }
}
