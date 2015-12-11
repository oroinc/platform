<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;

class OrganizationSearchHandler implements SearchHandlerInterface
{
    /** @var string */
    protected $className;

    /** @var array */
    protected $fields;

    /** @var array */
    protected $displayFields;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ServiceLink */
    protected $securityContextLink;

    /** @var PropertyAccessor */
    protected $accessor;

    /**
     * @param string          $className
     * @param array           $fields
     * @param array           $displayFields
     * @param ManagerRegistry $managerRegistry
     * @param ServiceLink     $securityContextLink
     */
    public function __construct(
        $className,
        $fields,
        $displayFields,
        ManagerRegistry $managerRegistry,
        ServiceLink $securityContextLink
    ) {
        $this->className = $className;
        $this->fields = $fields;
        $this->displayFields = $displayFields;
        $this->managerRegistry = $managerRegistry;
        $this->securityContextLink = $securityContextLink;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $user = $this->securityContextLink->getService()->getToken()->getUser();
        /** @var OrganizationRepository $repository */
        $repository = $this->managerRegistry->getRepository($this->className);
        if (!$searchById) {
            $items = $repository->getEnabledByUserAndName($user, $query);
        } else {
            $items = $repository->getEnabledUserOrganizationById($user, $query);
        }

        $resultsData = [];
        foreach ($items as $organization) {
            $resultsData[] = $this->convertItem($organization);
        }

        return [
            'results' => $resultsData,
            'more' => false
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->displayFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return $this->className;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = [];
        foreach ($this->fields as $field) {
            $result[$field] = $this->accessor->getValue($item, $field);
        }

        return $result;
    }
}
