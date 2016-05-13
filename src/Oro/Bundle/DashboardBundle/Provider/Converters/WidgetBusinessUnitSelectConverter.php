<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Provider\BusinessUnitAclProvider;

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

    /** @var BusinessUnitAclProvider */
    protected $businessUnitAclProvider;

    /**
     * @param EntityRepository $businessUnitRepository
     * @param SecurityFacade $securityFacade
     * @param BusinessUnitAclProvider $businessUnitAclProvider
     */
    public function __construct(
        EntityRepository $businessUnitRepository,
        SecurityFacade $securityFacade,
        BusinessUnitAclProvider $businessUnitAclProvider
    ) {
        $this->businessUnitRepository = $businessUnitRepository;
        $this->securityFacade = $securityFacade;
        $this->businessUnitAclProvider = $businessUnitAclProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        if ($value === null) {
            return $this->getBusinessUnitList($config);
        }

        return parent::getConvertedValue($widgetConfig, $value, $config, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormValue(array $converterAttributes, $value)
    {
        if ($value === null) {
            return $this->getBusinessUnitList($converterAttributes);
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

    /**
     * @param array $config
     * @return array
     */
    public function getBusinessUnitList($config)
    {
        $aclClass = isset($config['aclClass']) ? $config['aclClass'] : null;
        $aclPermission = isset($config['aclPermission']) ? $config['aclPermission'] : null;
        $queryBuilder = $this->businessUnitRepository->createQueryBuilder('businessUnit');

        if ($aclClass && $aclPermission) {

            $businessUnitIds = $this
                ->businessUnitAclProvider
                ->getBusinessUnitIds($aclClass, $aclPermission);

            if (!is_array($businessUnitIds) || count($businessUnitIds) === 0) {
                $businessUnitIds = [0];
            }

            $queryBuilder->andWhere($queryBuilder->expr()->in('businessUnit.id', $businessUnitIds));
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
