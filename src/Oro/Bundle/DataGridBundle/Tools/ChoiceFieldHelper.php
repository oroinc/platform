<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\DoctrineUtils\ORM\Walker\TranslatableSqlWalker;

/**
 * Provide label and choices for a given entity
 */
class ChoiceFieldHelper
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AclHelper */
    protected $aclHelper;

    public function __construct(DoctrineHelper $doctrineHelper, AclHelper $aclHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param ClassMetadata $metadata
     * @param string $columnName
     *
     * @return string
     *
     * @throws \Exception
     */
    public function guessLabelField($metadata, $columnName)
    {
        $labelField = '';

        if ($metadata->hasField('label')) {
            $labelField = 'label';
        } elseif ($metadata->hasField('name')) {
            $labelField = 'name';
        } else {
            //get first field with type "string"
            $isStringFieldPresent = false;
            foreach ($metadata->getFieldNames() as $fieldName) {
                if ($metadata->getTypeOfField($fieldName) === "string") {
                    $labelField = $fieldName;
                    $isStringFieldPresent = true;
                    break;
                }
            }

            if (!$isStringFieldPresent) {
                throw new \Exception(
                    "Could not find any field for using as label for 'choices' of '$columnName' column."
                );
            }
        }

        return $labelField;
    }

    /**
     * @param null|array $orderBy [field => direction]
     * @param array $whereOptions [field => value]
     *
     * @return array
     */
    public function getChoices(
        string $entity,
        string $keyField,
        string $labelField,
        ?array $orderBy = null,
        bool $translatable = false,
        array $whereOptions = []
    ) {
        $entityManager = $this->doctrineHelper->getEntityManager($entity);
        $queryBuilder = $entityManager
            ->getRepository($entity)
            ->createQueryBuilder('e');
        //select only id and label fields
        $queryBuilder->select(
            QueryBuilderUtil::getField('e', $keyField),
            QueryBuilderUtil::getField('e', $labelField)
        );
        foreach ($whereOptions as $key => $value) {
            $queryBuilder->andWhere(QueryBuilderUtil::sprintf('e.%s = :value_%s', $key, $key));
            $queryBuilder->setParameter(sprintf('value_%s', $key), $value);
        }
        if (!empty($orderBy)) {
            $field = array_keys($orderBy)[0];
            $queryBuilder->orderBy(
                QueryBuilderUtil::getField('e', $field),
                QueryBuilderUtil::getSortOrder($orderBy[$field])
            );
        }

        $query = $this->aclHelper->apply($queryBuilder);
        if ($translatable) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslatableSqlWalker::class);
        }

        $result = $query->getResult();
        $choices = [];
        foreach ($result as $item) {
            $choices[$item[$labelField]] = $item[$keyField];
        }

        return $choices;
    }
}
