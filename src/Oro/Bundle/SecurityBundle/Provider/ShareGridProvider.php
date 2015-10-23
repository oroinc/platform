<?php

namespace Oro\Bundle\SecurityBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ShareGridProvider
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ShareScopeProvider */
    protected $shareScopeProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param SecurityFacade $securityFacade
     * @param EntityClassNameHelper $entityClassNameHelper
     * @param ConfigManager $configManager
     * @param ShareScopeProvider $shareScopeProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        SecurityFacade $securityFacade,
        EntityClassNameHelper $entityClassNameHelper,
        ConfigManager $configManager,
        ShareScopeProvider $shareScopeProvider,
        TranslatorInterface $translator
    ) {
        $this->securityFacade = $securityFacade;
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->configManager = $configManager;
        $this->shareScopeProvider = $shareScopeProvider;
        $this->translator = $translator;
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    public function getSupportedGridsInfo($entityClass)
    {
        $entityClass = $this->entityClassNameHelper->resolveEntityClass($entityClass);
        $results = [];
        if (!$this->configManager->hasConfig($entityClass)) {
            return $results;
        }
        $shareScopes = $this->configManager->getProvider('security')->getConfig($entityClass)->get('share_scopes');
        if (!$shareScopes) {
            return $results;
        }
        $classNames = $this->shareScopeProvider->getClassNamesBySharingScopes($shareScopes);
        foreach ($classNames as $className) {
            $results[] = [
                'isGranted' => $this->securityFacade->isGranted('VIEW', 'entity:' . $className),
                'label' => $this->getClassLabel($className),
                'className' => $className,
                'first' => false,
                'gridName' => $this->getGridName($className),
            ];
        }
        usort(
            $results,
            function ($itemA) {
                return $itemA['isGranted'] ? -1 : 1;
            }
        );

        !empty($results[0]) && $results[0]['first'] = true;

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
        $label = $this->configManager->getProvider('entity')->getConfig($className)->get('label');

        return $this->translator->trans($label);
    }

    /**
     * @param $className
     *
     * @return null|string
     */
    public function getGridName($className)
    {
        $className = $this->entityClassNameHelper->resolveEntityClass($className);
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }

        return $this->configManager->getProvider('security')->getConfig($className)->get('share_grid');
    }
}
