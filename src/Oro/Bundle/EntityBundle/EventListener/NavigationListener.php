<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class NavigationListener
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param SecurityFacade      $securityFacade
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->securityFacade = $securityFacade;
        $this->configManager  = $configManager;
        $this->translator     = $translator;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $menu     = $event->getMenu();
        $children = array();

        $entitiesMenuItem = $menu->getChild('system_tab')->getChild('entities_list');
        if ($entitiesMenuItem) {
            /** @var ConfigProvider $entityConfigProvider */
            $entityConfigProvider = $this->configManager->getProvider('entity');

            /** @var ConfigProvider $entityExtendProvider */
            $entityExtendProvider = $this->configManager->getProvider('extend');

            $extendConfigs = $entityExtendProvider->getConfigs();

            foreach ($extendConfigs as $extendConfig) {
                if ($this->checkAvailability($extendConfig)) {
                    $config = $entityConfigProvider->getConfig($extendConfig->getId()->getClassname());
                    if (!class_exists($config->getId()->getClassName()) ||
                        !$this->securityFacade->hasLoggedUser() ||
                        !$this->securityFacade->isGranted('VIEW', 'entity:' . $config->getId()->getClassName())
                    ) {
                        continue;
                    }

                    $children[$config->get('label')] = array(
                        'label'   => $this->translator->trans($config->get('label')),
                        'options' => array(
                            'route'           => 'oro_entity_index',
                            'routeParameters' => array(
                                'entityName' => str_replace('\\', '_', $config->getId()->getClassName())
                            ),
                            'extras'          => array(
                                'safe_label' => true,
                                'routes'     => array('oro_entity_*')
                            ),
                        )
                    );
                }
            }

            sort($children);
            foreach ($children as $child) {
                $entitiesMenuItem->addChild($child['label'], $child['options']);
            }
        }

    }

    /**
     * @param Config $extendConfig
     *
     * @return bool
     */
    protected function checkAvailability(Config $extendConfig)
    {
        return
            $extendConfig->is('is_extend')
            && $extendConfig->get('owner') == ExtendScope::OWNER_CUSTOM
            && $extendConfig->in(
                'state',
                [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]
            );
    }
}
