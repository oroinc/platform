<?php

namespace Oro\Bundle\SecurityBundle\Request\ParamConverter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter as BaseParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DoctrineParamConverter extends BaseParamConverter
{
    /** @var RequestAuthorizationChecker */
    protected $requestAuthorizationChecker;

    /**
     * @param ManagerRegistry             $registry
     * @param RequestAuthorizationChecker $requestAuthorizationChecker
     */
    public function __construct(
        ManagerRegistry $registry = null,
        RequestAuthorizationChecker $requestAuthorizationChecker = null
    ) {
        parent::__construct($registry);

        $this->requestAuthorizationChecker = $requestAuthorizationChecker;
    }

    /**
     * Stores the object in the request.
     *
     * @param Request        $request
     * @param ParamConverter $configuration
     *
     * @return bool
     *
     * @throws AccessDeniedException When User doesn't have permission to the object
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $request->attributes->set('_oro_access_checked', false);
        $isSet = parent::apply($request, $configuration);

        if (null !== $this->requestAuthorizationChecker && $isSet) {
            $object = $request->attributes->get($configuration->getName());
            if ($object) {
                $granted = $this->requestAuthorizationChecker->isRequestObjectIsGranted($request, $object);
                if ($granted === -1) {
                    $acl = $this->requestAuthorizationChecker->getRequestAcl($request);
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

    /**
     * {@inheritdoc}
     *
     * The default Symfony's implementation is overridden to avoid loading
     * all entity managers if an entity belongs to the default manager.
     * Also avoid unnecessary "isTransient" call if the entity manager name is not configured.
     * @see \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter::supports
     */
    public function supports(ParamConverter $configuration)
    {
        if (null === $this->registry) {
            return false;
        }

        $className = $configuration->getClass();
        if (null === $className) {
            return false;
        }

        $options = $this->getOptions($configuration);
        $emName = $options['entity_manager'];
        if (null === $emName) {
            return null !== $this->registry->getManagerForClass($className);
        }

        return !$this->registry->getManager($emName)
            ->getMetadataFactory()
            ->isTransient($className);
    }
}
