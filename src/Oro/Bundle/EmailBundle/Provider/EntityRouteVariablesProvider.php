<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Translation\TranslatorInterface;

class EntityRouteVariablesProvider implements EntityVariablesProviderInterface
{
    const PREFIX = 'url';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var EntityConfigManager */
    protected $entityConfigManager;

    /**
     * EntityVariablesProvider constructor.
     *
     * @param TranslatorInterface $translator
     * @param EntityConfigManager $entityConfigManager
     */
    public function __construct(
        TranslatorInterface $translator,
        EntityConfigManager $entityConfigManager
    ) {
        $this->translator = $translator;
        $this->entityConfigManager = $entityConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions($entityClass = null)
    {
        if ($entityClass) {
            // process the specified entity only
            return $this->getEntityVariableDefinitions($entityClass);
        }

        // process all entities
        $result = [];
        $entityIds = $this->entityConfigManager->getProvider('entity')->getIds();
        foreach ($entityIds as $entityId) {
            $className = $entityId->getClassName();
            $entityData = $this->getEntityVariableDefinitions($className);
            if (!empty($entityData)) {
                $result[$className] = $entityData;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableGetters($entityClass = null)
    {
        return [];
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    protected function getEntityVariableDefinitions($entityClass)
    {
        $entityClass = ClassUtils::getRealClass($entityClass);
        $extendConfigProvider = $this->entityConfigManager->getProvider('extend');
        if (!$extendConfigProvider->hasConfig($entityClass)
            || !ExtendHelper::isEntityAccessible($extendConfigProvider->getConfig($entityClass))
        ) {
            return [];
        }

        $result = [];

        foreach ($this->getEntityRoutes($entityClass) as $name => $route) {
            if ($name === 'name') {
                $name = 'index';
            }
            $result[self::PREFIX . '.' . $name] = [
                'type' => 'string',
                'label' => $this->translator->trans(sprintf('oro.email.emailtemplate.variables.url.%s.label', $name)),
                'processor' => 'entity_routes',
                'route' => $route,
            ];
        }

        return $result;
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    protected function getEntityRoutes($entityClass)
    {
        $routes = [];

        $metadata = $this->entityConfigManager->getEntityMetadata($entityClass);

        if ($metadata) {
            $routes = $metadata->getRoutes();
        }

        if (ExtendHelper::isCustomEntity($entityClass)) {
            $extendedRoutes = [
                'name' => 'oro_entity_index',
                'view' => 'oro_entity_view',
            ];
            $routes = array_merge($routes, $extendedRoutes);
        }

        return $routes;
    }
}
