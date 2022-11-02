<?php

namespace Oro\Bundle\DataGridBundle\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;

/**
 * Accepts datagrid extensions to datagrid datasource, results object or metadata object
 */
class Acceptor
{
    /** @var DatagridConfiguration */
    protected $config;

    /** @var ExtensionVisitorInterface[] */
    protected $extensions = [];

    /**
     * Ask extensions to process configuration
     */
    public function processConfiguration()
    {
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $extension->processConfigs($this->getConfig());
        }
    }

    public function acceptDatasource(DatasourceInterface $datasource)
    {
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $extension->visitDatasource($this->getConfig(), $datasource);
        }
    }

    public function acceptResult(ResultsObject $result)
    {
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $extension->visitResult($this->getConfig(), $result);
        }
    }

    public function acceptMetadata(MetadataObject $data)
    {
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $extension->visitMetadata($this->getConfig(), $data);
        }
    }

    /**
     * Adds an extension that applicable to datagrid
     *
     * @param ExtensionVisitorInterface $extension
     *
     * @return $this
     */
    public function addExtension(ExtensionVisitorInterface $extension)
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * Sorts extensions by priority
     */
    public function sortExtensionsByPriority()
    {
        $comparisonClosure = function (ExtensionVisitorInterface $a, ExtensionVisitorInterface $b) {
            if ($a->getPriority() === $b->getPriority()) {
                # For PHP 7 we returned 0, but for PHP 8, to preserve the order after sort, we should return -1.
                # This is just a workaround that should be replaced with the explicit order for dependent extensions.
                return -1;
            }

            return $a->getPriority() > $b->getPriority() ? -1 : 1;
        };

        // https://bugs.php.net/bug.php?id=50688
        @usort($this->extensions, $comparisonClosure);

        return $this;
    }

    /**
     * Returns extensions applicable to datagrid
     *
     * @return ExtensionVisitorInterface[]
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Setter for config
     *
     * @param DatagridConfiguration $config
     *
     * @return mixed
     */
    public function setConfig(DatagridConfiguration $config)
    {
        $this->config = $config;

        return $config;
    }

    /**
     * Getter for config
     *
     * @return DatagridConfiguration
     */
    public function getConfig()
    {
        return $this->config;
    }
}
