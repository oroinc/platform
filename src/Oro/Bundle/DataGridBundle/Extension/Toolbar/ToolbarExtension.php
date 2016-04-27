<?php

namespace Oro\Bundle\DataGridBundle\Extension\Toolbar;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class ToolbarExtension extends AbstractExtension
{
    /**
     * Configuration tree paths
     */
    const METADATA_KEY = 'options';

    const OPTIONS_PATH                         = '[options]';
    const TOOLBAR_OPTION_PATH                  = '[options][toolbarOptions]';
    const PAGER_ITEMS_OPTION_PATH              = '[options][toolbarOptions][pageSize][items]';
    const PAGER_DEFAULT_PER_PAGE_OPTION_PATH   = '[options][toolbarOptions][pageSize][default_per_page]';
    const PAGER_ONE_PAGE_OPTION_PATH           = '[options][toolbarOptions][pagination][onePage]';
    const TURN_OFF_TOOLBAR_RECORDS_NUMBER_PATH = '[options][toolbarOptions][turnOffToolbarRecordsNumber]';
    const TOOLBAR_PAGINATION_HIDE_OPTION_PATH  = '[options][toolbarOptions][pagination][hide]';

    /** @var ConfigManager */
    private $cm;

    /**
     * @param ConfigManager $cm
     */
    public function __construct(ConfigManager $cm)
    {
        $this->cm = $cm;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $options = $config->offsetGetByPath(self::TOOLBAR_OPTION_PATH, []);
        // validate configuration and pass default values back to config
        $configuration = $this->validateConfiguration(new Configuration($this->cm), ['toolbarOptions' => $options]);
        $config->offsetSetByPath(sprintf('%s[%s]', self::OPTIONS_PATH, 'toolbarOptions'), $configuration);
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $result->offsetSetByPath('[options][hideToolbar]', false);
        $minToolbarRecords = (int)$config->offsetGetByPath(self::TURN_OFF_TOOLBAR_RECORDS_NUMBER_PATH);
        if ($minToolbarRecords > 0 && count($result->getData()) < $minToolbarRecords) {
            $result->offsetSetByPath('[options][hideToolbar]', true);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        /**
         * Default toolbar options
         *  [
         *      'hide'       => false,
         *      'pageSize'   => [
         *          'hide'  => false,
         *          'items' => [10, 25, 50, 100],
         *          'default_per_page' => 10
         *       ],
         *      'pagination' => [
         *          'hide' => false,
         *          'onePage' => false,
         *      ]
         *  ];
         */

        $perPageDefault = $config->offsetGetByPath(self::PAGER_DEFAULT_PER_PAGE_OPTION_PATH);
        $pageSizeItems  = $config->offsetGetByPath(self::PAGER_ITEMS_OPTION_PATH);

        $exist = array_filter(
            $pageSizeItems,
            function ($item) use ($perPageDefault) {
                if (is_array($item) && isset($item['size'])) {
                    return $perPageDefault == $item['size'];
                } elseif (is_numeric($item)) {
                    return $perPageDefault == $item;
                }

                return false;
            }
        );

        if (empty($exist)) {
            throw new LogicException(
                sprintf('Default page size "%d" must present in size items array', $perPageDefault)
            );
        }

        $options = $config->offsetGetByPath(ToolbarExtension::OPTIONS_PATH, []);

        // get user specified require js modules from options
        if (isset($options[MetadataObject::REQUIRED_MODULES_KEY])) {
            $data->offsetAddToArray(
                MetadataObject::REQUIRED_MODULES_KEY,
                $options[MetadataObject::REQUIRED_MODULES_KEY]
            );
            unset($options[MetadataObject::REQUIRED_MODULES_KEY]);
        }

        // in case of one page pagination page selector should be hidden
        if ($config->offsetGetByPath(self::PAGER_ONE_PAGE_OPTION_PATH, false)) {
            $options['toolbarOptions']['pageSize']['hide'] = true;
        }

        // grid options passed under "options" node
        $data->offsetAddToArray(self::METADATA_KEY, $options);
    }
}
