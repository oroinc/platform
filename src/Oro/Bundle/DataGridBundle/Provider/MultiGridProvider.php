<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MultiGridProvider
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ManagerInterface */
    protected $gridManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ConfigManager                 $configManager
     * @param ManagerInterface              $gridManager
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ConfigManager $configManager,
        ManagerInterface $gridManager
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->configManager = $configManager;
        $this->gridManager = $gridManager;
    }

    /**
     * @param string $entityClass
     *
     * @return string|null
     */
    public function getContextGridByEntity($entityClass)
    {
        if (ExtendHelper::isCustomEntity($entityClass)) {
            return 'custom-entity-grid';
        }

        $config = $this->configManager->getProvider('grid')->getConfig($entityClass);
        if ($config->has('context')) {
            return $config->get('context');
        }

        if ($config->has('default')) {
            return $config->get('default');
        }
    }

    /**
     * @param string[] $classNames
     *
     * @return array
     * [
     *     [
     *         'label' => label,
     *         'gridName' => gridName,
     *         'className' => className,
     *     ],
     * ]
     */
    public function getEntitiesData(array $classNames)
    {
        sort($classNames);

        $result = [];
        foreach ($classNames as $className) {
            $data = $this->getEntityData($className);
            if ($data) {
                $result[] = $data;
            }
        }

        return $result;
    }

    /**
     * @param string $className
     *
     * @return array|null
     * [
     *     'label' => label,
     *     'gridName' => gridName,
     *     'className' => className,
     * ]
     */
    protected function getEntityData($className)
    {
        if (!$this->authorizationChecker->isGranted('VIEW', sprintf('entity:%s', $className))) {
            return null;
        }

        $gridName = $this->getContextGridByEntity($className);
        if (!$gridName || !$this->isGridAllowed($gridName)) {
            return null;
        }

        return [
            'label'     => $this->getLabel($className),
            'gridName'  => $gridName,
            'className' => $className,
        ];
    }

    /**
     * @param string $entityClass
     *
     * @return string|null
     */
    protected function getLabel($entityClass)
    {
        return $this->configManager->getProvider('entity')->getConfig($entityClass)->get('label');
    }

    /**
     * @param string $gridName
     *
     * @return bool
     */
    protected function isGridAllowed($gridName)
    {
        $gridConfig = $this->gridManager->getConfigurationForGrid($gridName);
        $acl = $gridConfig ? $gridConfig->getAclResource() : null;

        return $this->authorizationChecker->isGranted($acl);
    }
}
