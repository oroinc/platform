<?php

namespace Oro\Bundle\LocaleBundle\Acl\Voter;

use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

class LocalizationVoter extends AbstractEntityVoter
{
    /** @var array */
    protected $supportedAttributes = [
        'DELETE'
    ];

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->isLastLocalization()) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @return bool
     */
    protected function isLastLocalization()
    {
        /** @var LocalizationRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->className);

        return $repository->getLocalizationsCount() <= 1;
    }
}
