<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;

/**
 * Column options Guesser for extend field configs.
 */
class ExtendColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function guessFormatter($class, $property, $type)
    {
        switch ($type) {
            case 'enum':
                $enumFieldConfig = $this->getFieldConfig('enum', $class, $property);
                if ($enumFieldConfig) {
                    $options = [
                        'frontend_type' => Property::TYPE_HTML,
                        'type' => 'twig',
                        'template' => '@OroEntityExtend/Datagrid/Property/enum.html.twig',
                        'context' => [
                            'enum_code' => $enumFieldConfig->get('enum_code'),
                        ]
                    ];
                }
                break;
            case 'multiEnum':
                $enumFieldConfig = $this->getFieldConfig('enum', $class, $property);
                if ($enumFieldConfig) {
                    $options = [
                        'frontend_type' => Property::TYPE_HTML,
                        'export_type' => 'list',
                        'type' => 'twig',
                        'template' => '@OroEntityExtend/Datagrid/Property/multiEnum.html.twig',
                        'context' => [
                            'enum_code' => $enumFieldConfig->get('enum_code'),
                        ]
                    ];
                }
                break;
        }

        return isset($options)
            ? new ColumnGuess($options, ColumnGuess::MEDIUM_CONFIDENCE)
            : null;
    }

    #[\Override]
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

    #[\Override]
    public function guessFilter($class, $property, $type)
    {
        switch ($type) {
            case 'enum':
                if ($this->getFieldConfig('extend', $class, $property)) {
                    $options = [
                        'type' => 'enum',
                        'null_value' => ':empty:',
                        'class' => EnumOption::class,
                        'enum_code' => $this->getFieldConfig('enum', $class, $property)?->get('enum_code')
                    ];
                }
                break;
            case 'multiEnum':
                if ($this->getFieldConfig('extend', $class, $property)) {
                    $options = [
                        'type' => 'multi_enum',
                        'null_value' => ':empty:',
                        'class' => EnumOption::class,
                        'enum_code' => $this->getFieldConfig('enum', $class, $property)?->get('enum_code')
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
