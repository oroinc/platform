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
     * @var Localization
     */
    protected $currentLocalization;

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
        // TODO: should be fixed in BB-3367. getCurrentLocalization method should return correct value.

        if (!$this->currentLocalization) {
            $items = $this->getRepository()->findBy([], ['id' => 'ASC']);

            $withEnglish = array_filter(
                $items,
                function (Localization $localization) {
                    return $localization->getLanguageCode() === 'en';
                }
            );

            $this->currentLocalization = $withEnglish ? reset($withEnglish) : reset($items);
        }

        return $this->currentLocalization;
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
