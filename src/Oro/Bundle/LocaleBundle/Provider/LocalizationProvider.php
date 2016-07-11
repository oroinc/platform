<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationProvider
{
    /**
     * @var ObjectRepository
     */
    protected $registry;

    /**
     * @param ObjectRepository $repository
     */
    public function __construct(ObjectRepository $repository)
    {
        $this->repository = $repository;
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
         //TODO: Implement getting of Default Localization

        $localizations = $this->getLocalizations();
        if (count($localizations)) {
            return reset($localizations);
        }

        return null;
    }
}
