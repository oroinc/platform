<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Provider\BusinessUnitAclProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;

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

    /** @var string */
    protected $aclEntityClass;

    /** @var string */
    protected $aclPermission;

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
            return $this->getBusinessUnitList();
        }

        return parent::getConvertedValue($widgetConfig, $value, $config, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormValue(array $converterAttributes, $value)
    {
        if ($value === null) {
            return $this->getBusinessUnitList();
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
     * @param string $aclEntityClass
     * @param string $aclPermission
     */
    public function addAclByEntityPermission($aclEntityClass, $aclPermission)
    {
        $this->aclEntityClass = $aclEntityClass;
        $this->aclPermission = $aclPermission;
    }

    /**
     * @return array
     */
    protected function getBusinessUnitList()
    {
        $queryBuilder = $this->businessUnitRepository->getQueryBuilderByOrganization(
            $this->securityFacade->getOrganizationId()
        );

        if ($this->aclEntityClass && $this->aclPermission) {
            $observer = new OneShotIsGrantedObserver();
            $businessUnitIds = $this
                ->businessUnitAclProvider
                ->addOneShotIsGrantedObserver($observer)
                ->getBusinessUnitIds($this->aclEntityClass, $this->aclPermission);
            $queryBuilder->andWhere($queryBuilder->expr()->in('businessUnit.id', $businessUnitIds));
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
