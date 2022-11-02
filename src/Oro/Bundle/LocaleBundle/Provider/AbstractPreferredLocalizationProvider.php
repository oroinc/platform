<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Base class for preferred localization providers contains logic of handling calls for not supported entities.
 */
abstract class AbstractPreferredLocalizationProvider implements PreferredLocalizationProviderInterface
{
    /**
     * @param object $entity
     * @return Localization|null
     */
    abstract protected function getPreferredLocalizationForEntity($entity): ?Localization;

    /**
     * {@inheritdoc}
     */
    public function getPreferredLocalization($entity): ?Localization
    {
        if (!$this->supports($entity)) {
            throw new \LogicException(
                sprintf('"%s" entity class is not supported by "%s" provider', \get_class($entity), \get_class($this))
            );
        }

        return $this->getPreferredLocalizationForEntity($entity);
    }
}
