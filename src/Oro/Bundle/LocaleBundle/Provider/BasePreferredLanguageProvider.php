<?php

namespace Oro\Bundle\LocaleBundle\Provider;

/**
 * Base class for preferred language providers contains logic of handling calls for not supported entities.
 */
abstract class BasePreferredLanguageProvider implements PreferredLanguageProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPreferredLanguage($entity): string
    {
        if (!$this->supports($entity)) {
            throw new \LogicException(
                sprintf('"%s" entity class is not supported by "%s" provider', \get_class($entity), \get_class($this))
            );
        }

        return $this->getPreferredLanguageForEntity($entity);
    }

    /**
     * @param object $entity
     * @return string
     */
    abstract protected function getPreferredLanguageForEntity($entity): string;
}
