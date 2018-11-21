<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Exception\LinkHrefResolvingFailedException;

/**
 * The metadata that represents a link to an external resource and related to a particular entity.
 */
class ExternalLinkMetadata extends LinkMetadata
{
    /** @var string URL template; parameters must be enclosed with curly brackets, e.g. http://test.com?entity={id} */
    private $urlTemplate;

    /** @var array [parameter name => parameter property path or NULL if it equals to the name, ...] */
    private $urlParams;

    /** @var array [parameter name => scalar value, ...] */
    private $defaultParams;

    /**
     * @param string $urlTemplate   The URL template. It can contains "{param}" placeholders
     *                              that should be replaced with values of corresponding parameters.
     * @param array  $urlParams     The URL parameters.
     *                              [name => property path or NULL if ir equals to the name, ...]
     *                              The property path can starts with "_." to get access to an entity data.
     *                              The "__type__" property can be used to get an entity type.
     *                              The "__class__" property can be used to get an entity class.
     *                              The "__id__" property can be used to get an entity identifier.
     *                              See {@see \Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface}.
     * @param array  $defaultParams The default values for unresolved URL parameters.
     *                              [name => scalar value, ...]
     */
    public function __construct(
        string $urlTemplate,
        array $urlParams = [],
        array $defaultParams = []
    ) {
        $this->urlTemplate = $urlTemplate;
        $this->urlParams = $urlParams;
        $this->defaultParams = $defaultParams;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result['url_template'] = $this->urlTemplate;
        if (!empty($this->urlParams)) {
            $result['url_params'] = $this->urlParams;
        }
        if (!empty($this->defaultParams)) {
            $result['default_params'] = $this->defaultParams;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getHref(DataAccessorInterface $dataAccessor): ?string
    {
        if (empty($this->urlParams)) {
            return $this->urlTemplate;
        }

        $params = $this->resolveParameters($dataAccessor, $this->urlParams);
        if (!empty($this->defaultParams)) {
            $params = array_merge($params, $this->defaultParams);
        }
        $missingParams = array_diff(array_keys($this->urlParams), array_keys($params));
        if (!empty($missingParams)) {
            throw new LinkHrefResolvingFailedException(
                sprintf(
                    'Cannot build URL for a link. Missing Parameters: %s.',
                    implode(',', $missingParams)
                )
            );
        }

        $url = $this->urlTemplate;
        foreach ($params as $name => $value) {
            $url = str_replace('{' . $name . '}', (string)$value, $url);
        }

        return $url;
    }
}
