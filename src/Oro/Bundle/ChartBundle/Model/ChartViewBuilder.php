<?php

namespace Oro\Bundle\ChartBundle\Model;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface as DataGridManager;

use Oro\Bundle\ChartBundle\Exception\BadMethodCallException;
use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;

use Oro\Bundle\ChartBundle\Model\Data\MappedData;
use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataGridData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;

class ChartViewBuilder
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var DataGridManager
     */
    protected $dataGridManager;

    /**
     * @var DataInterface
     */
    protected $data;

    /**
     * @var array
     */
    protected $dataMapping;

    /**
     * Array of chart options
     *
     * array(
     *     "name" => "chart_name",
     *     "data_schema" => array(
     *         "label" => array("fieldName" => "name", "label" => "oro.xxx.firstName"),
     *         "value" => array("fieldName" => "salary", "label" => "oro.xxx.salary"),
     *     ),
     *     "settings" => array(
     *         "foo" => "bar"
     *     ),
     * )
     *
     * @var array
     */
    protected $options;

    /**
     * @param ConfigProvider $configProvider
     * @param \Twig_Environment $twig
     * @param DataGridManager $manager
     */
    public function __construct(ConfigProvider $configProvider, \Twig_Environment $twig, DataGridManager $manager)
    {
        $this->configProvider = $configProvider;
        $this->twig = $twig;
        $this->dataGridManager = $manager;
    }

    /**
     * Set chart data
     *
     * @param DataInterface $data
     * @return ChartViewBuilder
     */
    public function setData(DataInterface $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set chart data as array
     *
     * @param array $data
     * @return ChartViewBuilder
     */
    public function setArrayData(array $data)
    {
        $this->setData(new ArrayData($data));

        return $this;
    }

    /**
     * Set chart data as grid name, grid data will used
     *
     * @param array $name
     * @return ChartViewBuilder
     */
    public function setDataGridName($name)
    {
        $this->setData(new DataGridData($this->dataGridManager, $name));

        return $this;
    }

    /**
     * Set data mapping
     *
     * @param array $dataMapping
     * @return ChartViewBuilder
     */
    public function setDataMapping(array $dataMapping)
    {
        $this->dataMapping = $dataMapping;

        return $this;
    }

    /**
     * Set chart options
     *
     * @param array $options
     * @return ChartViewBuilder
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Build chart view
     *
     * @return ChartView
     */
    public function getView()
    {
        $data = $this->getData();
        $vars = $this->getVars();

        return new ChartView($this->twig, $vars['config']['template'], $data, $vars);
    }

    /**
     * Get chart view vars
     *
     * @return array
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    protected function getVars()
    {
        $options = $this->options;

        if (null === $options) {
            throw new BadMethodCallException('Can\'t build result when setOptions() was not called.');
        }

        if (!isset($options['name'])) {
            throw new InvalidArgumentException('Options must have "name" key.');
        }

        if (!isset($options['settings']) || !is_array($options['settings'])) {
            $options['settings'] = array();
        }

        $config = $this->configProvider->getChartConfig($options['name']);

        if (!isset($config['template'])) {
            throw new InvalidArgumentException(
                sprintf('Config of chart "%s" must have "template" key.', $options['name'])
            );
        }

        if (isset($config['default_settings']) && is_array($config['default_settings'])) {
            $options['settings'] = array_replace_recursive($config['default_settings'], $options['settings']);
        }

        return array(
            'options' => $options,
            'config' => $config
        );
    }

    /**
     * Get chart data
     *
     * @return DataInterface
     * @throws BadMethodCallException
     */
    protected function getData()
    {
        if (null === $this->data) {
            throw new BadMethodCallException("Can't build result when setData() was not called.");
        }

        if (null !== $this->dataMapping) {
            return new MappedData($this->dataMapping, $this->data);
        }

        return $this->data;
    }
}
