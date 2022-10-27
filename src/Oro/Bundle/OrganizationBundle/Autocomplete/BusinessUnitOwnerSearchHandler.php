<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

/**
 * The autocomplete handler to search business units that can be an owner for other entities.
 */
class BusinessUnitOwnerSearchHandler extends SearchHandler
{
    protected ManagerRegistry $doctrine;

    public function __construct($entityName, array $properties, ManagerRegistry $doctrine)
    {
        parent::__construct($entityName, $properties);
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = parent::convertItem($item);

        $businessUnit = $this->doctrine->getManager()->getRepository(BusinessUnit::class)
            ->find($result[$this->idFieldName]);

        $result['treePath'] = $this->getPath($businessUnit, []);
        $result['organization_id'] = $businessUnit->getOrganization()->getId();

        return $result;
    }

    /**
     * @param BusinessUnit $businessUnit
     * @param $path
     *
     * @return mixed
     */
    protected function getPath($businessUnit, $path)
    {
        array_unshift($path, ['name'=> $businessUnit->getName()]);

        $owner = $businessUnit->getOwner();
        if ($owner) {
            $path = $this->getPath($owner, $path);
        }

        return $path;
    }
}
