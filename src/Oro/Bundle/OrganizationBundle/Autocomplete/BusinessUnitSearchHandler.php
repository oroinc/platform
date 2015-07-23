<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Doctrine\ORM\EntityManager;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Entity\User;

class BusinessUnitSearchHandler implements SearchHandlerInterface
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var string */
    protected $className;

    /** @var array */
    protected $fields;

    /** @var array */
    protected $displayFields;

    /** @var ServiceLink */
    protected $serviceLink;

    /** @var PropertyAccessor */
    protected $accessor;

    /**
     * @param EntityManager $entityManager
     * @param string $className
     * @param array $fields
     * @param array $displayFields
     * @param ServiceLink $serviceLink
     */
    public function __construct(
        EntityManager $entityManager,
        $className,
        $fields,
        $displayFields,
        ServiceLink $serviceLink
    ) {
        $this->entityManager = $entityManager;
        $this->className = $className;
        $this->fields = $fields;
        $this->displayFields = $displayFields;
        $this->serviceLink = $serviceLink;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $resultsData = [];

        /** @var User $user */
        $user = $this->getSecurityContext()->getToken()->getUser();
        $hasMore = false;
        if ($user && $user->getId()) {
            $units = $user->getBusinessUnits()->map(
                function (BusinessUnit $businessUnit) {
                    return $businessUnit->getId();
                }
            );
            $units = $units->toArray();
            if ($units) {
                $page        = (int) $page > 0 ? (int) $page : 1;
                $perPage     = (int) $perPage > 0 ? (int) $perPage : 10;
                $firstResult = ($page - 1) * $perPage;
                ++$perPage;

                $queryBuilder = $this->entityManager->createQueryBuilder()
                    ->select('bu')
                    ->from('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit', 'bu');
                if ($query) {
                    $queryBuilder->where($queryBuilder->expr()->like('bu.name', ':query'))
                        ->setParameter('query', '%' . str_replace(' ', '%', $query) . '%');
                }
                $queryBuilder->andWhere($queryBuilder->expr()->in('bu.id', ':units'))
                    ->setParameter('units', $units)
                    ->setFirstResult($firstResult)
                    ->setMaxResults($perPage);
                $results = $queryBuilder->getQuery()->getResult();
                $hasMore = count($results) === $perPage;
                foreach ($results as $bu) {
                    $resultsData[] = $this->convertItem($bu);
                }
            }
        }

        return [
            'results' => $resultsData,
            'more' => $hasMore
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

    /**
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->serviceLink->getService();
    }
}
