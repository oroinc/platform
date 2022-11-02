<?php

namespace Oro\Bundle\SecurityBundle\Request\ParamConverter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter as BaseParamConverter;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Check access to entity
 */
class DoctrineParamConverter extends BaseParamConverter
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var RequestAuthorizationChecker */
    protected $requestAuthorizationChecker;

    /** @var array */
    private $defaultOptions;

    public function __construct(
        ManagerRegistry $registry = null,
        ExpressionLanguage $expressionLanguage = null,
        RequestAuthorizationChecker $requestAuthorizationChecker = null,
        array $options = []
    ) {
        parent::__construct($registry, $expressionLanguage, $options);

        $this->registry = $registry;
        $this->requestAuthorizationChecker = $requestAuthorizationChecker;

        $defaultValues = [
            'entity_manager' => null,
            'exclude' => [],
            'mapping' => [],
            'strip_null' => false,
            'expr' => null,
            'id' => null,
            'repository_method' => null,
            'map_method_signature' => false,
            'evict_cache' => false,
        ];

        $this->defaultOptions = array_merge($defaultValues, $options);
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

        $options = array_replace($this->defaultOptions, $configuration->getOptions());
        $emName = $options['entity_manager'];
        if (null === $emName) {
            return null !== $this->registry->getManagerForClass($className);
        }

        return !$this->registry->getManager($emName)
            ->getMetadataFactory()
            ->isTransient($className);
    }
}
