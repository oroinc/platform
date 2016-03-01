<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

/**
 * Class WidgetBusinessUnitSelectConverter
 * @package Oro\Bundle\DashboardBundle\Provider\Converters
 */
class WidgetBusinessUnitSelectConverter extends ConfigValueConverterAbstract
{
    /** @var EntityRepository */
    protected $businessUnitRepository;

    /**
     * @param EntityRepository $businessUnitRepository
     */
    public function __construct(EntityRepository $businessUnitRepository)
    {
        $this->businessUnitRepository = $businessUnitRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        if ($value === null) {
            return $this->businessUnitRepository->findAll();
        }

        return parent::getConvertedValue($widgetConfig, $value, $config, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormValue(array $converterAttributes, $value)
    {
        if ($value === null) {
            return $this->businessUnitRepository->findAll();
        }

        return parent::getFormValue($converterAttributes, $value);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function getViewValue($value)
    {
        return empty($value) ? null : implode('; ', $value);
    }
}
