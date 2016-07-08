<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;

class LocalizationProvider
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param int $id
     *
     * @return null|Localization
     */
    public function getLocalization($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @return array|Localization[]
     */
    public function getLocalizations()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * @return LocalizationRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass(Localization::class)->getRepository(Localization::class);
    }
}
