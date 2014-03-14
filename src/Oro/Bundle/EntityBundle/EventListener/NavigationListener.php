<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Symfony\Component\Translation\Translator;

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

    /** @var ConfigProvider $entityConfigProvider */
    protected $entityConfigProvider = null;

    /** @var ConfigProvider $entityExtendProvider */
    protected $entityExtendProvider = null;

    /** @var  Translator */
    protected $translator;

    /**
     * @param SecurityFacade $securityFacade
     * @param ConfigProvider $entityConfigProvider
     * @param ConfigProvider $entityExtendProvider
     * @param Translator     $translator
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $entityExtendProvider,
        Translator $translator
    ) {
        $this->securityFacade       = $securityFacade;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->entityExtendProvider = $entityExtendProvider;
        $this->translator           = $translator;
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
            $extendConfigs = $this->entityExtendProvider->getConfigs();

            foreach ($extendConfigs as $extendConfig) {
                if ($extendConfig->is('is_extend')
                    && $extendConfig->get('owner') == ExtendScope::OWNER_CUSTOM
                    && $extendConfig->in(
                        'state',
                        [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATED]
                    )
                ) {
                    $config = $this->entityConfigProvider->getConfig($extendConfig->getId()->getClassname());
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
                                'id' => str_replace('\\', '_', $config->getId()->getClassName())
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
}
