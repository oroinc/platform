<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

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
            $queryBuilder = $this->businessUnitRepository->createQueryBuilder('bu');
            if ($organizationId = $this->securityFacade->getOrganizationId()) {
                $queryBuilder->andWhere('bu.organization = :organizationId');
                $queryBuilder->setParameter('organizationId', $organizationId);
            }
            $this->applyAclByEntityPermission($queryBuilder);
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
            $this->applyAclByEntityPermission($queryBuilder);
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
     * @param QueryBuilder $queryBuilder
     */
    protected function applyAclByEntityPermission(QueryBuilder $queryBuilder)
    {
        if ($this->aclEntityClass && $this->aclPermission) {
            $businessUnitIds = $this
                ->businessUnitAclProvider
                ->getBusinessUnitIds($this->aclEntityClass, $this->aclPermission);
            $queryBuilder->andWhere($queryBuilder->expr()->in('bu.id', $businessUnitIds));
        }
    }
}
