<?php

namespace Oro\Bundle\ChartBundle\Model;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface as DataGridManager;

use Oro\Bundle\ChartBundle\Exception\BadMethodCallException;
use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;

use Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerFactory;
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
     *         "label" => array("field_name" => "name", "label" => "oro.xxx.firstName"),
     *         "value" => array("field_name" => "salary", "label" => "oro.xxx.salary"),
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
     * @param TransformerFactory $transformerFactory
     * @param \Twig_Environment $twig
     * @param DataGridManager $manager
     */
    public function __construct(
        ConfigProvider $configProvider,
        TransformerFactory $transformerFactory,
        DataGridManager $manager,
        \Twig_Environment $twig
    ) {
        $this->configProvider = $configProvider;
        $this->transformerFactory = $transformerFactory;
        $this->dataGridManager = $manager;
        $this->twig = $twig;
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

        if (isset($this->options['data_schema']) && is_array($this->options['data_schema'])) {
            $dataMapping = array();
            foreach ($this->options['data_schema'] as $key => $data) {
                $dataMapping[$key] = isset($data['field_name']) ? $data['field_name'] : $key;
            }
            $this->setDataMapping($dataMapping);
        }

        return $this;
    }

    /**
     * Build chart view
     *
     * @return ChartView
     */
    public function getView()
    {
        $vars = $this->getVars();
        $data = $this->getData($vars);

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
     * @param array $vars
     * @return DataInterface
     * @throws BadMethodCallException
     */
    protected function getData(array $vars)
    {
        if (null === $this->data) {
            throw new BadMethodCallException("Can't build result when setData() was not called.");
        }

        $result = $this->data;

        if (null !== $this->dataMapping) {
            $result = new MappedData($this->dataMapping, $result);
        }

        if (isset($vars['config']['data_transformer'])) {
            $transformer = $this->transformerFactory->createTransformer($vars['config']['data_transformer']);
            $result = $transformer->transform($result, $vars['options']);
        }

        return $result;
    }
}
