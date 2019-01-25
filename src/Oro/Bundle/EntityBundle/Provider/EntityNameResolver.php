<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides functionality to get human-readable text representation of an entity.
 */
class EntityNameResolver
{
    /** @var string */
    private $defaultFormat;

    /** @var array */
    private $config;

    /** @var array */
    private $normalizedConfig;

    /** @var iterable|EntityNameProviderInterface[] */
    private $providers;

    /**
     * @param iterable|EntityNameProviderInterface[] $providers     The entity name providers
     * @param string                                 $defaultFormat The default representation format
     * @param array                                  $config        The configuration of representation formats
     *
     * @throws \InvalidArgumentException if default format is not specified or does not exist
     */
    public function __construct(iterable $providers, string $defaultFormat, array $config)
    {
        if (!$defaultFormat) {
            throw new \InvalidArgumentException('The default representation format must be specified.');
        }
        if (!isset($config[$defaultFormat])) {
            throw new \InvalidArgumentException(
                sprintf('The unknown default representation format "%s".', $defaultFormat)
            );
        }

        $this->providers = $providers;
        $this->defaultFormat = $defaultFormat;
        $this->config = $config;
    }

    /**
     * Returns a text representation of the given entity.
     *
     * @param object                   $entity The entity object
     * @param string|null              $format The representation format, for example full, short, etc.
     *                                         If not specified a default representation is used
     * @param string|null|Localization $locale The representation locale.
     *                                         If not specified a default locale is used
     *
     * @return string A text representation of an entity or NULL if the name cannot be resolved
     */
    public function getName($entity, $format = null, $locale = null)
    {
        if (null === $entity) {
            return null;
        }

        $formats = $this->getFormatConfig($format ?: $this->defaultFormat);
        foreach ($formats as $currentFormat) {
            foreach ($this->providers as $provider) {
                $val = $provider->getName($currentFormat['name'], $locale, $entity);
                if (false !== $val) {
                    return $val;
                }
            }
        }

        return null;
    }

    /**
     * Returns a DQL expression that can be used to get a text representation of the given type of entities.
     *
     * @param string                   $className The FQCN of the entity
     * @param string                   $alias     The alias in SELECT or JOIN statement
     * @param string|null              $format    The representation format, for example full, short, etc.
     *                                            If not specified a default representation is used
     * @param string|null|Localization $locale    The representation locale.
     *                                            If not specified a default locale is used
     *
     * @return string A DQL expression or NULL if the name cannot be resolved
     */
    public function getNameDQL($className, $alias, $format = null, $locale = null)
    {
        QueryBuilderUtil::checkIdentifier($alias);

        $formats = $this->getFormatConfig($format ?: $this->defaultFormat);
        foreach ($formats as $currentFormat) {
            foreach ($this->providers as $provider) {
                $val = $provider->getNameDQL($currentFormat['name'], $locale, $className, $alias);
                if (false !== $val) {
                    return $val;
                }
            }
        }

        return null;
    }

    /**
     * Returns an expression that is ready to be used in DQL query.
     * * An empty expression is replaces with ''
     * * If casting to a string is requested the expression is wrapped with CAST(expr AS string)
     *
     * @param string|null $expr         The name DQL expression
     * @param bool        $castToString Whether the given DQL expression should be casted to a string
     *                                  Usually this is required for UNION and UNION ALL queries
     *
     * @return string
     */
    public function prepareNameDQL($expr, $castToString = false)
    {
        if (!$expr) {
            $expr = '\'\'';
        } elseif ($castToString) {
            // For UNION and UNION ALL queries an expression need to be forcibly converted to string
            // to avoid errors like this:
            // "UNION types text and integer cannot be matched"
            // "Illegal mix of collations for operation 'UNION'"
            $expr = sprintf('CAST(%s AS string)', $expr);
        }

        return $expr;
    }

    /**
     * Returns the configuration of the given format
     *
     * @param string $format
     *
     * @return array
     *
     * @throws \InvalidArgumentException if unknown format is passes
     */
    protected function getFormatConfig($format)
    {
        if (null === $this->normalizedConfig) {
            $this->normalizedConfig = $this->normalizeConfig($this->config);
        }
        if (!isset($this->normalizedConfig[$format])) {
            throw new \InvalidArgumentException(sprintf('The unknown representation format "%s".', $format));
        }

        return $this->normalizedConfig[$format];
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function normalizeConfig($config)
    {
        $result = [];
        $names = array_keys($config);
        foreach ($names as $name) {
            $fallback = $name;
            while ($fallback) {
                $format = $config[$fallback];
                $format['name'] = $fallback;
                $result[$name][] = $format;
                $fallback = $format['fallback'];
            }
        }

        return $result;
    }
}
