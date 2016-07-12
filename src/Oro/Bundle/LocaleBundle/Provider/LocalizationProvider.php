<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationProvider
{
    /**
     * @var ObjectRepository
     */
    protected $registry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ObjectRepository $repository
     * @param ConfigManager $configManager
     */
    public function __construct(ObjectRepository $repository, ConfigManager $configManager)
    {
        $this->repository = $repository;
        $this->configManager = $configManager;
    }

    /**
     * @param int $id
     *
     * @return null|Localization
     */
    public function getLocalization($id)
    {
        return $this->repository->find($id);
    }

    /**
     * @param array $ids
     *
     * @return array|Localization[]
     */
    public function getLocalizations(array $ids = null)
    {
        return $this->repository->findBy(!is_null($ids) ? ['id' => $ids] : [], ['name' => 'ASC']);
    }

    /**
     * @throws \Exception
     *
     * @return Localization
     */
    public function getDefaultLocalization()
    {
        $id = $this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION));

        $localization = $this->getLocalization($id);

        if ($localization instanceof Localization){
           return $localization;
        }

        $localizations = $this->getLocalizations();
        if (count($localizations)) {
            return reset($localizations);
        }


        return null;
    }
}
