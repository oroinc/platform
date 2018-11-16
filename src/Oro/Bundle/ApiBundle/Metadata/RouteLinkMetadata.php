<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Exception\LinkHrefResolvingFailedException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The metadata that represents a link to a API resource and related to a particular entity.
 */
class RouteLinkMetadata extends LinkMetadata
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var string */
    private $routeName;

    /** @var array [parameter name => parameter property path or NULL if it equals to the name, ...] */
    private $routeParams;

    /** @var array [parameter name => scalar value, ...] */
    private $defaultParams;

    /**
     * @param UrlGeneratorInterface $urlGenerator  The instance of a URL generator.
     * @param string                $routeName     The name of a route.
     * @param array                 $routeParams   The route parameters.
     *                                             [name => property path or NULL if it equals to the name, ...]
     *                                             The property path can starts with "_." to get access
     *                                             to an entity data.
     *                                             The "__type__" property can be used to get an entity type.
     *                                             The "__class__" property can be used to get an entity class.
     *                                             The "__id__" property can be used to get an entity identifier.
     *                                             See {@see \Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface}.
     * @param array                 $defaultParams The default values for unresolved route parameters.
     *                                             [name => scalar value, ...]
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        string $routeName,
        array $routeParams = [],
        array $defaultParams = []
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->routeName = $routeName;
        $this->routeParams = $routeParams;
        $this->defaultParams = $defaultParams;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result['route_name'] = $this->routeName;
        if (!empty($this->routeParams)) {
            $result['route_params'] = $this->routeParams;
        }
        if (!empty($this->defaultParams)) {
            $result['default_params'] = $this->defaultParams;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getHref(DataAccessorInterface $dataAccessor): ?string
    {
        $params = $this->resolveParameters($dataAccessor, $this->routeParams);
        if (!empty($this->defaultParams)) {
            $params = array_merge($params, $this->defaultParams);
        }
        try {
            return $this->urlGenerator->generate(
                $this->routeName,
                $params,
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } catch (InvalidParameterException | MissingMandatoryParametersException $e) {
            throw new LinkHrefResolvingFailedException(
                sprintf('Cannot build URL for a link. Reason: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
}
