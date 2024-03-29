<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides methods for querying email templates related information such as getting localized email template or
 * getting system only email templates.
 */
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
        return $this->findOneBy(['name' => $templateName]);
    }

    /**
     * Load templates by entity name
     *
     * @param AclHelper $aclHelper
     * @param string $entityName
     * @param Organization $organization
     * @param bool $includeNonEntity
     * @param bool $includeSystemTemplates
     *
     * @return EmailTemplate[]
     */
    public function getTemplateByEntityName(
        AclHelper $aclHelper,
        $entityName,
        Organization $organization,
        $includeNonEntity = false,
        $includeSystemTemplates = true
    ) {
        $qb = $this->getEntityTemplatesQueryBuilder(
            $entityName,
            $organization,
            $includeNonEntity,
            $includeSystemTemplates
        );

        if ($aclHelper === null) {
            return $qb->getQuery()->getResult();
        }

        return $aclHelper->apply($qb)->getResult();
    }

    /**
     * Return templates query builder filtered by entity name
     *
     * @param string $entityName entity class
     * @param Organization $organization
     * @param bool $includeNonEntity if true - system templates will be included in result set
     * @param bool $includeSystemTemplates
     * @param bool $visibleOnly
     * @param array $excludeNames
     *
     * @return QueryBuilder
     */
    public function getEntityTemplatesQueryBuilder(
        $entityName,
        Organization $organization,
        $includeNonEntity = false,
        $includeSystemTemplates = true,
        $visibleOnly = true,
        array $excludeNames = []
    ): QueryBuilder {
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

        if ($excludeNames) {
            $qb->andWhere($qb->expr()->notIn('e.name', ':excludeNames'))
                ->setParameter('excludeNames', $excludeNames);
        }

        $qb->andWhere('e.organization = :organization')
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
            ->where('e.entityName IS NOT NULL')
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

    public function findWithLocalizations(
        EmailTemplateCriteria $emailTemplateCriteria,
        array $templateContext = []
    ): ?EmailTemplate {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('t', 'translations')
            ->leftJoin('t.translations', 'translations');

        $this->resolveEmailTemplateCriteria($queryBuilder, $emailTemplateCriteria, $templateContext);

        $result = $queryBuilder->getQuery()->getResult();

        return $result ? array_shift($result) : null;
    }

    public function isExist(EmailTemplateCriteria $emailTemplateCriteria, array $templateContext = []): bool
    {
        $queryBuilder = $this->createQueryBuilder('t')->select('1');
        $this->resolveEmailTemplateCriteria($queryBuilder, $emailTemplateCriteria, $templateContext);

        return (bool)$queryBuilder->getQuery()->getResult();
    }

    private function resolveEmailTemplateCriteria(
        QueryBuilder $queryBuilder,
        EmailTemplateCriteria $emailTemplateCriteria,
        array $templateContext = []
    ): void {
        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq('t.name', ':name'))
            ->setParameter('name', $emailTemplateCriteria->getName());

        if ($emailTemplateCriteria->getEntityName()) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('t.entityName', ':entityName'))
                ->setParameter('entityName', $emailTemplateCriteria->getEntityName());
        }

        $classMetadata = $this->getClassMetadata();
        foreach ($templateContext as $parameterName => $parameterValue) {
            if (!$classMetadata->hasField($parameterName) && !$classMetadata->hasAssociation($parameterName)) {
                continue;
            }

            if ($parameterValue === EmailTemplateCriteria::CONTEXT_PARAMETER_NULL) {
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->isNull(QueryBuilderUtil::getField('t', $parameterName)));
            } else {
                $queryBuilder
                    ->andWhere(
                        $queryBuilder->expr()->eq(
                            QueryBuilderUtil::getField('t', $parameterName),
                            QueryBuilderUtil::sprintf(':%s', $parameterName)
                        )
                    )
                    ->setParameter($parameterName, $parameterValue);
            }
        }
    }
}
