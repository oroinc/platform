<?php

namespace Oro\Bundle\DataGridBundle\Extension\Appearance;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class AppearanceExtension extends AbstractExtension
{
    const APPEARANCE_CONFIG_PATH = 'appearances';
    const APPEARANCE_OPTION_PATH = '[options][appearances]';

    const APPEARANCE_ROOT_PARAM = '_appearance';
    const APPEARANCE_TYPE_PARAM = '_type';
    const APPEARANCE_DATA_PARAM = '_data';

    const MINIFIED_APPEARANCE_TYPE_PARAM = 'a';
    const MINIFIED_APPEARANCE_DATA_PARAM = 'ad';

    /** @var Configuration */
    protected $configuration;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param Configuration $configuration
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Configuration $configuration,
        TranslatorInterface $translator
    ) {
        $this->configuration = $configuration;
        $this->translator     = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $options = $config->offsetGetOr(static::APPEARANCE_CONFIG_PATH, []);
        $hasOptions = count($options) > 0;

        return $hasOptions;
    }

     /**
     * @param ParameterBag $parameters
     */
    public function setParameters(ParameterBag $parameters)
    {
        if ($parameters->has(ParameterBag::MINIFIED_PARAMETERS)) {
            $minifiedParameters = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
            $appearance = [];

            if (array_key_exists(static::MINIFIED_APPEARANCE_TYPE_PARAM, $minifiedParameters)) {
                $appearance[static::APPEARANCE_TYPE_PARAM] =
                    $minifiedParameters[static::MINIFIED_APPEARANCE_TYPE_PARAM];
            }
            if (array_key_exists(static::MINIFIED_APPEARANCE_DATA_PARAM, $minifiedParameters)) {
                $appearance[static::APPEARANCE_DATA_PARAM] =
                    $minifiedParameters[static::MINIFIED_APPEARANCE_DATA_PARAM];
            }

            $parameters->set(static::APPEARANCE_ROOT_PARAM, $appearance);
        }

        parent::setParameters($parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $configs = $config->offsetGetOr(self::APPEARANCE_CONFIG_PATH, []);
        if ($configs) {
            $configs = $this->validateConfiguration(
                $this->configuration,
                ['appearances' => $configs]
            );
            $processedOptions = [];
            foreach ($configs as $type => $options) {
                if (isset($options[Configuration::DEFAULT_PROCESSING_KEY]) &&
                    $options[Configuration::DEFAULT_PROCESSING_KEY]) {
                    unset($options[Configuration::DEFAULT_PROCESSING_KEY]);
                    $options['label'] = $this->translator->trans($options['label']);
                    $processedOptions[] = array_merge(['type' => $type], $options);
                }
            }
            $config->offsetSetByPath(static::APPEARANCE_OPTION_PATH, $processedOptions);
        }
    }

     /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $options = $config->offsetGetOr(static::APPEARANCE_CONFIG_PATH, []);
        if (count($options) < 2) {
            $data->offsetUnsetByPath(static::APPEARANCE_OPTION_PATH);
        }
        $initialState = [
            'appearanceType' => Configuration::GRID_APPEARANCE_TYPE,
            'appearanceData' => []
        ];
        $state = [
            'appearanceType' => $this->getOr(static::APPEARANCE_TYPE_PARAM, Configuration::GRID_APPEARANCE_TYPE),
            'appearanceData' => $this->getOr(static::APPEARANCE_DATA_PARAM, [])
        ];
        $data->offsetAddToArray('initialState', $initialState);
        $data->offsetAddToArray('state', $state);
    }

    /**
     * Get param or return specified default value
     *
     * @param string $paramName
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getOr($paramName, $default = null)
    {
        $parameters = $this->getParameters()->get(static::APPEARANCE_ROOT_PARAM, []);

        return isset($parameters[$paramName]) ? $parameters[$paramName] : $default;
    }
}
