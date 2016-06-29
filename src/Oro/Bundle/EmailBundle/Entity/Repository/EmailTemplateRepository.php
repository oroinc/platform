<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class EmailTemplateRepository extends EntityRepository
{
    /**
     * Gets a template by its name
     * This method can return null if the requested template does not exist
     *
     * @param string $templateName
     * @return EmailTemplate|null
     */
    public function findByName($templateName)
    {
        return $this->findOneBy(array('name' => $templateName));
    }

    /**
     * Load templates by entity name
     *
     * @param string       $entityName
     * @param Organization $organization
     * @param bool         $includeNonEntity
     * @param bool         $includeSystemTemplates
     *
     * @return EmailTemplate[]
     */
    public function getTemplateByEntityName(
        $entityName,
        Organization $organization,
        $includeNonEntity = false,
        $includeSystemTemplates = true
    ) {
        return $this->getEntityTemplatesQueryBuilder(
            $entityName,
            $organization,
            $includeNonEntity,
            $includeSystemTemplates
        )
            ->getQuery()->getResult();
    }

    /**
     * Return templates query builder filtered by entity name
     *
     * @param string       $entityName    entity class
     * @param Organization $organization
     * @param bool         $includeNonEntity if true - system templates will be included in result set
     * @param bool         $includeSystemTemplates
     * @param bool         $visibleOnly
     *
     * @return QueryBuilder
     */
    public function getEntityTemplatesQueryBuilder(
        $entityName,
        Organization $organization,
        $includeNonEntity = false,
        $includeSystemTemplates = true,
        $visibleOnly = true
    ) {
        $qb = $this->createQueryBuilder('e')
            ->where('e.entityName = :entityName')
            ->orderBy('e.name', 'ASC')
            ->setParameter('entityName', $entityName);

        if ($includeNonEntity) {
            $qb->orWhere('e.entityName IS NULL');
        }

        if (!$includeSystemTemplates) {
            $qb->andWhere('e.isSystem = :isSystem')
                ->setParameter('isSystem', false);
        }

        if ($visibleOnly) {
            $qb->andWhere('e.visible = :visible')
                ->setParameter('visible', true);
        }

        $qb->andWhere("e.organization = :organization")
            ->setParameter('organization', $organization);

        return $qb;
    }

    /**
     * Return a query builder which can be used to get names of entities
     * which have at least one email template
     *
     * @return QueryBuilder
     */
    public function getDistinctByEntityNameQueryBuilder()
    {
        return $this->createQueryBuilder('e')
            ->select('e.entityName')
            ->distinct();
    }

    /**
     * Get templates without any related entity
     *
     * @return QueryBuilder
     */
    public function getSystemTemplatesQueryBuilder()
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.entityName IS NULL');

        return $qb;
    }
}
