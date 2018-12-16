<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to request excluding of custom fields.
 * @see \Oro\Bundle\ApiBundle\Processor\Config\Shared\EnsureInitialized
 * @see \Oro\Bundle\ApiBundle\Processor\Config\Shared\ExcludeCustomFields
 */
class ExcludeCustomFieldsConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'exclude_custom_fields';

    /** @var bool */
    private $exclude;

    /**
     * @param bool $exclude A flag indicates whether custom fields should be excluded or not
     */
    public function __construct(bool $exclude = true)
    {
        $this->exclude = $exclude;
    }

    /**
     * @return bool
     */
    public function isExclude()
    {
        return $this->exclude;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context)
    {
        // no any modifications of the ConfigContext is required
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return $this->exclude
            ? self::NAME
            : null;
    }
}
