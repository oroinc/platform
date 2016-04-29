<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Class WidgetBusinessUnitSelectConverter
 * @package Oro\Bundle\DashboardBundle\Provider\Converters
 */
class WidgetBusinessUnitSelectConverter extends ConfigValueConverterAbstract
{
    /** @var EntityRepository */
    protected $businessUnitRepository;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param EntityRepository $businessUnitRepository
     * @param SecurityFacade $securityFacade
     */
    public function __construct(EntityRepository $businessUnitRepository, SecurityFacade $securityFacade)
    {
        $this->businessUnitRepository = $businessUnitRepository;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        if ($value === null) {
            $queryBuilder = $this->businessUnitRepository->createQueryBuilder('bu');
            if ($organizationId = $this->securityFacade->getOrganizationId()) {
                $queryBuilder->andWhere('bu.organization = :organizationId');
                $queryBuilder->setParameter('organizationId', $organizationId);
            }
            return $queryBuilder->getQuery()->getResult();
        }

        return parent::getConvertedValue($widgetConfig, $value, $config, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormValue(array $converterAttributes, $value)
    {
        if ($value === null) {
            $queryBuilder = $this->businessUnitRepository->createQueryBuilder('bu');
            if ($organizationId = $this->securityFacade->getOrganizationId()) {
                $queryBuilder->andWhere('bu.organization = :organizationId');
                $queryBuilder->setParameter('organizationId', $organizationId);
            }
            return $queryBuilder->getQuery()->getResult();
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
