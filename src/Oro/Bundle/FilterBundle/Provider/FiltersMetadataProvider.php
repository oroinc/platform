<?php

namespace Oro\Bundle\FilterBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\RawConfigurationProvider;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides filters metadata usable in a datagrid.
 */
class FiltersMetadataProvider
{
    private RawConfigurationProvider $configurationProvider;
    private TranslatorInterface $translator;

    public function __construct(RawConfigurationProvider $configurationProvider, TranslatorInterface $translator)
    {
        $this->configurationProvider = $configurationProvider;
        $this->translator = $translator;
    }

    /**
     * Provides filters metadata for the specified filters and datagrid config.
     *
     * @param FilterInterface[]     $filters
     * @param DatagridConfiguration $gridConfig
     *
     * @return array
     */
    public function getMetadataForFilters(array $filters, DatagridConfiguration $gridConfig): array
    {
        $rawConfig = $filters ? $this->configurationProvider->getRawConfiguration($gridConfig->getName()) : null;

        foreach ($filters as $filter) {
            $metadata = $filter->getMetadata();
            if (!empty($metadata['label']) && !empty($metadata[FilterUtility::TRANSLATABLE_KEY])) {
                $metadata['label'] = $this->translator->trans($metadata['label']);
            }

            $metadata['cacheId'] = null;
            if ($rawConfig && !empty($metadata['lazy'])) {
                $metadata['cacheId'] = $this->getFilterCacheId($rawConfig, $metadata);
            }

            $filtersMetadata[] = $metadata;
        }

        return $filtersMetadata ?? [];
    }

    private function getFilterCacheId(array $rawGridConfig, array $filterMetadata): ?string
    {
        $rawOptions = ArrayUtil::getIn($rawGridConfig, ['filters', 'columns', $filterMetadata['name'], 'options']);

        return $rawOptions ? md5(serialize($rawOptions)) : null;
    }
}
