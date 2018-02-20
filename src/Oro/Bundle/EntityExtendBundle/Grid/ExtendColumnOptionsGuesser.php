<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class ExtendColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function guessFormatter($class, $property, $type)
    {
        switch ($type) {
            case 'enum':
                $extendFieldConfig = $this->getFieldConfig('extend', $class, $property);
                if ($extendFieldConfig) {
                    $options = [
                        'frontend_type' => Property::TYPE_HTML,
                        'type'          => 'twig',
                        'template'      => 'OroEntityExtendBundle:Datagrid:Property/enum.html.twig',
                        'context'       => [
                            'entity_class' => $extendFieldConfig->get('target_entity')
                        ]
                    ];
                }
                break;
            case 'multiEnum':
                $extendFieldConfig = $this->getFieldConfig('extend', $class, $property);
                if ($extendFieldConfig) {
                    $options = [
                        'frontend_type' => Property::TYPE_HTML,
                        'export_type'   => 'list',
                        'type'          => 'twig',
                        'template'      => 'OroEntityExtendBundle:Datagrid:Property/multiEnum.html.twig',
                        'context'       => [
                            'entity_class' => $extendFieldConfig->get('target_entity')
                        ]
                    ];
                }
                break;
        }

        return isset($options)
            ? new ColumnGuess($options, ColumnGuess::MEDIUM_CONFIDENCE)
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessSorter($class, $property, $type)
    {
        if ($type === 'multiEnum') {
            return new ColumnGuess(
                [Property::DISABLED_KEY => true],
                ColumnGuess::MEDIUM_CONFIDENCE
            );
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessFilter($class, $property, $type)
    {
        switch ($type) {
            case 'enum':
                $extendFieldConfig = $this->getFieldConfig('extend', $class, $property);
                if ($extendFieldConfig) {
                    $options = [
                        'type'       => 'enum',
                        'null_value' => ':empty:',
                        'class'      => $extendFieldConfig->get('target_entity')
                    ];
                }
                break;
            case 'multiEnum':
                $extendFieldConfig = $this->getFieldConfig('extend', $class, $property);
                if ($extendFieldConfig) {
                    $options = [
                        'type'       => 'multi_enum',
                        'null_value' => ':empty:',
                        'class'      => $extendFieldConfig->get('target_entity')
                    ];
                }
                break;
        }

        return isset($options)
            ? new ColumnGuess($options, ColumnGuess::MEDIUM_CONFIDENCE)
            : null;
    }

    /**
     * @param string $scope
     * @param string $class
     * @param string $property
     *
     * @return ConfigInterface
     */
    protected function getFieldConfig($scope, $class, $property)
    {
        $configProvider = $this->configManager->getProvider($scope);

        return $configProvider->hasConfig($class, $property)
            ? $configProvider->getConfig($class, $property)
            : null;
    }
}
