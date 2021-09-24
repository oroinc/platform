<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Generates url to the entity-related pages (view, update, create, index)
 */
class EntityUrlGenerator
{
    private EntityConfigManager $entityConfigManager;

    private UrlGeneratorInterface $urlGenerator;

    private EntityClassNameHelper $entityClassNameHelper;

    public function __construct(
        EntityConfigManager $entityConfigManager,
        UrlGeneratorInterface $urlGenerator,
        EntityClassNameHelper $entityClassNameHelper
    ) {
        $this->entityConfigManager = $entityConfigManager;
        $this->urlGenerator = $urlGenerator;
        $this->entityClassNameHelper = $entityClassNameHelper;
    }

    /**
     * @param string $className
     * @param string $routeName One of [view, update, create, name]
     * @param array $parameters Route parameters passed to Symfony url generator.
     * @param bool $fallbackToGeneralController
     * @param int $referenceType The type of reference to be generated (one of the Symfony url generator constants).
     *
     * @return string
     *
     * @throws RouteNotFoundException
     * @throws MissingMandatoryParametersException
     * @throws InvalidParameterException
     */
    public function generate(
        string $className,
        string $routeName,
        array $parameters = [],
        bool $fallbackToGeneralController = true,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $url = '';
        $metadata = $this->entityConfigManager->getEntityMetadata($className);

        if ($metadata && $metadata->hasRoute($routeName)) {
            if ($route = $metadata->getRoute($routeName, true)) {
                $url = $this->urlGenerator->generate($route, $parameters, $referenceType);
            }
        } elseif ($fallbackToGeneralController && $this->entityConfigManager->hasConfig($className)) {
            $extendConfig = $this->entityConfigManager->getProvider('extend')->getConfig($className);

            if ($extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
                $url = $this->urlGenerator->generate(
                    'oro_entity_update',
                    array_merge(
                        ['entityName' => $this->entityClassNameHelper->getUrlSafeClassName($className)],
                        $parameters
                    ),
                    $referenceType
                );
            }
        }

        return $url;
    }
}
