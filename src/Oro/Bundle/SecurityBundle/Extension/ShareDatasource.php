<?php

namespace Oro\Bundle\SecurityBundle\Extension;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\Formatter\ShareFormatter;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;

class ShareDatasource implements DatasourceInterface
{
    const TYPE = 'share';

    /** @var MutableAclProvider */
    protected $aclProvider;

    /** @var EntityRoutingHelper */
    protected $routingHelper;

    /** @var ObjectManager */
    protected $objectManager;

    /** @var ShareFormatter */
    protected $shareFormatter;

    /** @var object */
    protected $object;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param MutableAclProvider $aclProvider
     * @param EntityRoutingHelper $routingHelper
     * @param ObjectManager $objectManager
     * @param ShareFormatter $shareFormatter
     */
    public function __construct(
        MutableAclProvider $aclProvider,
        EntityRoutingHelper $routingHelper,
        ObjectManager $objectManager,
        ShareFormatter $shareFormatter
    ) {
        $this->aclProvider = $aclProvider;
        $this->routingHelper = $routingHelper;
        $this->objectManager = $objectManager;
        $this->shareFormatter = $shareFormatter;
    }

    /**
     * {@inheritDoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        $parameters = $grid->getParameters();
        $this->object = $this->routingHelper->getEntity($parameters->get('entityClass'), $parameters->get('entityId'));
        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $this->init();
        $objects = $this->getObjects();
        $rows = $this->getRows($objects);

        return $rows;
    }

    /**
     * Additional initialization on demand
     */
    protected function init()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Returns objects extracted from objectIdentity
     *
     * @return array
     */
    protected function getObjects()
    {
        $objects = [];
        $objectIdentity = ObjectIdentity::fromDomainObject($this->object);
        try {
            $acl = $this->aclProvider->findAcl($objectIdentity);
        } catch (AclNotFoundException $e) {
            // no ACL found, do nothing
            $acl = null;
        }

        if (!$acl) {
            return $objects;
        }

        $buIds = [];
        $usernames = [];
        foreach ($acl->getObjectAces() as $ace) {
            /** @var $ace Entry */
            $securityIdentity = $ace->getSecurityIdentity();
            if ($securityIdentity instanceof UserSecurityIdentity) {
                $usernames[] = $securityIdentity->getUsername();
            } elseif ($securityIdentity instanceof BusinessUnitSecurityIdentity) {
                $buIds[] = $securityIdentity->getId();
            }
        }
        if ($buIds) {
            /** @var $repo BusinessUnitRepository */
            $repo = $this->objectManager->getRepository('OroOrganizationBundle:BusinessUnit');
            $businessUnits = $repo->getBusinessUnits($buIds);
            $objects = array_merge($objects, $businessUnits);
        }
        if ($usernames) {
            /** @var $repo UserRepository */
            $repo = $this->objectManager->getRepository('OroUserBundle:User');
            $users = $repo->findUsersByUsernames($usernames);
            $objects = array_merge($objects, $users);
        }

        return $objects;
    }

    /**
     * Returns rows which are structured for "Who has access" datagrid
     *
     * @param array $objects
     *
     * @return array
     */
    protected function getRows($objects)
    {
        $rows = [];
        foreach ($objects as $object) {
            $rows[] = new ResultRecord([
                'id' => json_encode([
                    'entityId' => (string) $this->propertyAccessor->getValue($object, 'id'),
                    'entityClass' => ClassUtils::getRealClass($object),
                ]),
                'entity' => $this->shareFormatter->getEntityDetails($object),
            ]);
        }

        return $rows;
    }
}
