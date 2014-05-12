<?php

namespace Oro\Bundle\ChartBundle\Model;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

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
     * @var TransformerFactory
     */
    protected $transformerFactory;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var array
     */
    protected $datagridColumnsDefinition;

    /**
     * @var DataInterface
     */
    protected $data;

    /**
     * @var array
     */
    protected $dataMapping;

    /**
     * @var array
     */
    protected $chartConfig;

    /**
     * Array of chart options.
     *
     * array(
     *     "name" => "chart_name",
     *     "data_schema" => array(
     *         "label" => array("field_name" => "name", "label" => "First Name", "type" => "string"),
     *         "value" => array("field_name" => "salary", "label" => "Salary", "type" => "money"),
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
     */
    public function __construct(
        ConfigProvider $configProvider,
        TransformerFactory $transformerFactory,
        \Twig_Environment $twig
    ) {
        $this->configProvider = $configProvider;
        $this->transformerFactory = $transformerFactory;
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
     * Set chart data as grid instance
     *
     * @param DatagridInterface $datagrid
     * @return ChartViewBuilder
     */
    public function setDataGrid(DatagridInterface $datagrid)
    {
        $this->setData(new DataGridData($datagrid));

        $config = $datagrid->getConfig();
        $this->datagridColumnsDefinition = $config['columns'];

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
        if (array_values($dataMapping) === array_keys($dataMapping)) {
            $this->dataMapping = null;
        } else {
            $this->dataMapping = $dataMapping;
        }

        return $this;
    }

    /**
     * Set chart options
     *
     * @param array $options
     * @return ChartViewBuilder
     * @throws InvalidArgumentException
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        $this->chartConfig = null;

        if (!isset($this->options['name'])) {
            throw new InvalidArgumentException('Options must have "name" key.');
        }

        if (!isset($this->options['settings']) || !is_array($this->options['settings'])) {
            $this->options['settings'] = array();
        }

        $this->parseOptionsDataSchema();
        $this->setupDataMapping();

        return $this;
    }

    /**
     * Parse "data_schema" key of options, fix structure and update fields using datagrid
     *
     * @throws InvalidArgumentException
     */
    protected function parseOptionsDataSchema()
    {
        if (isset($this->options['data_schema'])) {
            if (!is_array($this->options['data_schema'])) {
                throw new InvalidArgumentException('Options must have "data_schema" key with array.');
            }

            foreach ($this->options['data_schema'] as $key => &$data) {
                $data = $this->parseOptionsDataSchemaField($key, $data);
            }
        }
    }

    /**
     * @param string $key
     * @param string|array $data
     * @return array
     */
    protected function parseOptionsDataSchemaField($key, $data)
    {
        if (!is_array($data)) {
            $data = array('field_name' => $data);
        }

        if (!isset($data['field_name'])) {
            $data['field_name'] = $key;
        }

        $fieldName = $data['field_name'];

        if (!isset($data['label']) && isset($this->datagridColumnsDefinition[$fieldName]['label'])) {
            $data['label'] = $this->datagridColumnsDefinition[$fieldName]['label'];
        }

        if (!isset($data['type']) && isset($this->datagridColumnsDefinition[$fieldName]['frontend_type'])) {
            $data['type'] = $this->datagridColumnsDefinition[$fieldName]['frontend_type'];
        }

        if (!isset($data['type'])) {
            $configFieldData = $this->getChartConfigDataSchemaField($key);
            $data['type'] = $configFieldData['default_type'];
        }

        return $data;
    }

    /**
     * Setup data mapping when options was set
     */
    protected function setupDataMapping()
    {
        if (isset($this->options['data_mapping'])) {
            $this->setDataMapping($this->options['data_mapping']);
        }

        if (null === $this->dataMapping && isset($this->options['data_schema'])) {
            $dataMapping = array();
            foreach ($this->options['data_schema'] as $key => $data) {
                $dataMapping[$key] = $data['field_name'];
            }
            $this->setDataMapping($dataMapping);
        }
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

        $config = $this->getChartConfig();

        if (!isset($config['template'])) {
            throw new InvalidArgumentException(
                sprintf('Config of chart "%s" must have "template" key.', $this->options['name'])
            );
        }

        $options['settings'] = array_replace_recursive($config['default_settings'], $options['settings']);

        return array(
            'options' => $options,
            'config' => $config
        );
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getChartConfig()
    {
        if (null === $this->chartConfig) {
            $chartConfig = $this->configProvider->getChartConfig($this->options['name']);
            $this->chartConfig = $chartConfig;
        }
        return $this->chartConfig;
    }

    /**
     * @param string $name
     * @return array
     */
    protected function getChartConfigDataSchemaField($name)
    {
        $chartConfig = $this->getChartConfig();
        if (isset($chartConfig['data_schema'])) {
            foreach ($chartConfig['data_schema'] as $data) {
                if ($data['name'] == $name) {
                    return $data;
                }
            }
        }

        return array();
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
