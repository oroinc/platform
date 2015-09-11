<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\Common\Cache\Cache;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class NavigationListener
{
    const CACHE_LIFETIME = 3600; // 1 hour
    const CACHE_KEY = 'menu.custom_entities';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var Cache */
    protected $cache;

    /**
     * @param SecurityFacade      $securityFacade
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     * @param Cache               $cache
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        Cache $cache
    ) {
        $this->securityFacade = $securityFacade;
        $this->configManager  = $configManager;
        $this->translator     = $translator;
        $this->cache          = $cache;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $entitiesMenuItem = $event->getMenu()->getChild('system_tab')->getChild('entities_list');
        if (!$entitiesMenuItem) {
            return;
        }

        if ($this->cache->contains(static::CACHE_KEY)) {
            $children = $this->cache->fetch(static::CACHE_KEY);
        } else {
            $children = $this->getCustomEntitiesMenuConfig();
            $this->cache->save(static::CACHE_KEY, $children, static::CACHE_LIFETIME);
        }

        foreach ($children as $child) {
            $entitiesMenuItem->addChild($child['label'], $child['options']);
        }
    }

    /**
     * @return array
     */
    protected function getCustomEntitiesMenuConfig()
    {
        $children = [];

        /** @var ConfigProvider $entityConfigProvider */
        /** @var ConfigProvider $entityExtendProvider */
        /** @var ConfigProvider $groupConfigProvider */
        $entityConfigProvider = $this->configManager->getProvider('entity');
        $entityExtendProvider = $this->configManager->getProvider('extend');
        $groupConfigProvider  = $this->configManager->getProvider('grouping');

        $withHidden    = true; // TODO: move it to configuration
        $extendConfigs = $entityExtendProvider->getConfigs(null, $withHidden);
        $groupConfigs  = $groupConfigProvider->getConfigs(null, $withHidden);

        foreach ($extendConfigs as $extendConfig) {
            $groupConfig = array_shift($groupConfigs);

            if (!$this->isEntityMenuAvailable($extendConfig, $groupConfig)) {
                continue;
            }

            $className    = $extendConfig->getId()->getClassname();
            $entityConfig = $entityConfigProvider->getConfig($className);

            if (!class_exists($entityConfig->getId()->getClassName()) ||
                !$this->securityFacade->hasLoggedUser() ||
                !$this->securityFacade->isGranted('VIEW', 'entity:'.$className)
            ) {
                continue;
            }

            $label = $entityConfig->get('label') ?:
                sprintf(
                    'oro.custom_entity.%s.plural_label',
                    strtolower(ExtendHelper::getShortClassName($className))
                );

            $children[$label] = [
                'label'   => $this->translator->trans($label),
                'options' => [
                    'route'           => 'oro_entity_index',
                    'routeParameters' => [
                        'entityName' => str_replace('\\', '_', $className)
                    ],
                    'extras'          => [
                        'safe_label' => true,
                        'routes'     => ['oro_entity_*']
                    ],
                ]
            ];
        }

        sort($children);

        return $children;
    }

    /**
     * @param ConfigInterface $extendConfig
     * @param ConfigInterface $groupConfig
     *
     * @return bool
     */
    protected function isEntityMenuAvailable(ConfigInterface $extendConfig, ConfigInterface $groupConfig)
    {
        return
            // custom entity
            $extendConfig->get('owner') == ExtendScope::OWNER_CUSTOM
            // with active state
            && $extendConfig->in(
                'state',
                [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]
            )
            && (
                // is extend entity
                $extendConfig->is('is_extend')
                ||
                // or located in dictionary group OR is an enum
                (
                    in_array(GroupingScope::GROUP_DICTIONARY, $groupConfig->get('groups', false, []))
                    || false !== strpos($extendConfig->getId()->getClassName(), ExtendHelper::ENTITY_NAMESPACE . 'EV_')
                )
            )
            ;
    }
}
