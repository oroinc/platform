<?php

namespace Oro\Bundle\SecurityBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Search\AclHelper;

class ShareGridProvider
{
    /** @var EntityRoutingHelper */
    protected $routingHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var AclHelper */
    protected $helper;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param EntityRoutingHelper $routingHelper
     * @param ConfigManager $configManager
     * @param AclHelper $helper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EntityRoutingHelper $routingHelper,
        ConfigManager $configManager,
        AclHelper $helper,
        TranslatorInterface $translator
    ) {
        $this->routingHelper = $routingHelper;
        $this->configManager = $configManager;
        $this->helper = $helper;
        $this->translator = $translator;
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    public function getSupportedGridsInfo($entityClass)
    {
        $entityClass = $this->routingHelper->resolveEntityClass($entityClass);
        $results = [];
        if (!$this->configManager->hasConfig($entityClass)) {
            return $results;
        }
        $entityConfigId = new EntityConfigId('security', $entityClass);
        $shareScopes = $this->configManager->getConfig($entityConfigId)->get('share_scopes');
        if (!$shareScopes) {
            return $results;
        }
        $classNames = $this->helper->getClassNamesBySharingScopes($shareScopes);
        foreach ($classNames as $key => $className) {
            $results[] = [
                'label' => $this->getClassLabel($className),
                'className' => $className,
                'first' => !(bool) $key,
                'gridName' => $this->getGridName($className),
            ];
        }

        return $results;
    }

    /**
     * @param string $className
     *
     * @return null|string
     */
    protected function getClassLabel($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }
        $entityConfigId = new EntityConfigId('entity', $className);
        $label = $this->configManager->getConfig($entityConfigId)->get('label');

        return $this->translator->trans($label);
    }

    /**
     * @param $className
     *
     * @return null|string
     */
    public function getGridName($className)
    {
        $className = $this->routingHelper->resolveEntityClass($className);
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }
        $entityConfigId = new EntityConfigId('entity', $className);

        return $this->configManager->getConfig($entityConfigId)->get('share_with_datagrid');
    }
}
