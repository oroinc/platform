<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides definitions of entity route variables.
 */
class EntityRouteVariablesProvider implements EntityVariablesProviderInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(TranslatorInterface $translator, ConfigManager $configManager)
    {
        $this->translator = $translator;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions(): array
    {
        $result = [];
        $entityIds = $this->configManager->getIds('entity');
        foreach ($entityIds as $entityId) {
            $className = $entityId->getClassName();
            $entityData = $this->getEntityVariableDefinitions($className);
            if ($entityData) {
                $result[$className] = $entityData;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableGetters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableProcessors(string $entityClass): array
    {
        return $this->getEntityVariableProcessors($entityClass);
    }

    private function getEntityVariableDefinitions(string $entityClass): array
    {
        $result = [];
        $routes = $this->getEntityRoutes($entityClass);
        foreach ($routes as $name => $route) {
            $result['url.' . $name] = [
                'type'  => 'string',
                'label' => $this->translator->trans($this->getVariableLabel($name)),
            ];
        }

        return $result;
    }

    private function getEntityVariableProcessors(string $entityClass): array
    {
        $result = [];
        $routes = $this->getEntityRoutes($entityClass);
        foreach ($routes as $name => $route) {
            $result['url.' . $name] = [
                'processor' => 'entity_routes',
                'route'     => $route
            ];
        }

        return $result;
    }

    /**
     * @param string $entityClass
     *
     * @return string[]
     */
    private function getEntityRoutes(string $entityClass): array
    {
        $metadata = $this->getEntityMetadata($entityClass);
        $routes = null !== $metadata
            ? $metadata->getRoutes()
            : [];

        if (ExtendHelper::isCustomEntity($entityClass)) {
            $routes = array_merge($routes, [
                'name' => 'oro_entity_index',
                'view' => 'oro_entity_view'
            ]);
        }

        if (isset($routes['name'])) {
            $routes['index'] = $routes['name'];
            unset($routes['name']);
        }

        return $routes;
    }

    private function getEntityMetadata(string $entityClass): ?EntityMetadata
    {
        if (!$this->configManager->hasConfig($entityClass)
            || !ExtendHelper::isEntityAccessible($this->configManager->getEntityConfig('extend', $entityClass))
        ) {
            return null;
        }

        return $this->configManager->getEntityMetadata($entityClass);
    }

    private function getVariableLabel(string $name): string
    {
        return sprintf('oro.email.emailtemplate.variables.url.%s.label', $name);
    }
}
