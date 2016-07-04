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
        $items = $this->getRepository()->findBy([], ['id' => 'ASC']);

        $withEnglish = array_filter(
            $items,
            function (Localization $localization) {
                return $localization->getLanguageCode() === 'en';
            }
        );

        return $withEnglish ? reset($withEnglish) : reset($items);
    }

    /**
     * @return Localization[]
     */
    public function getAll()
    {
        return $this->getRepository()->getBatchIterator();
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
