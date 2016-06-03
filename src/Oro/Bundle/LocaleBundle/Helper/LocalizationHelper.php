<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;

class LocalizationHelper
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * LocaleHelper constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @return Localization
     */
    public function getCurrentLocalization()
    {
        //return $this->getRepository()->findOneByCode('en');
        return $this->getRepository()->findOneByLanguageCode('en');
    }

    /**
     * @return Localization[]
     */
    public function getAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * @return LocalizationRepository
     */
    protected function getRepository()
    {
        $repo = $this->registry
            ->getManagerForClass($this->entityClass)
            ->getRepository($this->entityClass);

        return $repo;
    }
}
