<?php

namespace Oro\Bundle\TranslationBundle\Form\ChoiceList;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Translatable\Hydrator\ORM\ObjectHydrator;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Translation\TranslatableQueryTrait;
use Oro\Component\DoctrineUtils\ORM\Walker\TranslatableSqlWalker;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * Implementation of ChoiceLoaderInterface for TranslatableEntityType.
 * It loads all entities of a given class name or entities restricted by query builder to use for choices.
 */
class TranslationChoiceLoader implements ChoiceLoaderInterface
{
    use TranslatableQueryTrait;

    private string $className;
    private QueryBuilder|\Closure|null $queryBuilder;
    private ManagerRegistry $doctrine;
    private ChoiceListFactoryInterface $factory;
    private ?AclHelper $aclHelper;
    private array $aclOptions;
    private ?ChoiceListInterface $choiceList = null;

    public function __construct(
        string $className,
        ManagerRegistry $doctrine,
        ChoiceListFactoryInterface $factory,
        QueryBuilder|\Closure|null $queryBuilder,
        ?AclHelper $aclHelper = null,
        array $aclOptions = []
    ) {
        $this->className = $className;
        $this->queryBuilder = $queryBuilder;
        $this->doctrine = $doctrine;
        $this->factory = $factory;
        $this->aclHelper = $aclHelper;
        $this->aclOptions = $aclOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoiceList($value = null)
    {
        if (null !== $this->choiceList) {
            return $this->choiceList;
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManager();

        // translation must not be selected separately for each entity
        $entityManager->getConfiguration()->addCustomHydrationMode(
            TranslatableSqlWalker::HYDRATE_OBJECT_TRANSLATION,
            ObjectHydrator::class
        );

        // make entity translatable
        $query = $this->resolveQueryBuilder()->getQuery();
        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            TranslatableSqlWalker::class
        );
        $this->addTranslatableLocaleHint($query, $entityManager);

        // In case we use not standard Hydrator (not Query::HYDRATE_OBJECT)
        // we should add this hint to load nested entities
        // otherwise Doctrine will create partial object
        $query->setHint(Query::HINT_INCLUDE_META_COLUMNS, true);

        // Protects the query with ACL.
        if (null !== $this->aclHelper
            && (!isset($this->aclOptions['disable']) || true !== $this->aclOptions['disable'])
        ) {
            $options = $this->aclOptions['options'] ?? [];
            $permission = $this->aclOptions['permission'] ?? 'VIEW';
            $this->aclHelper->apply($query, $permission, $options);
        }

        $entities = $query->execute(null, TranslatableSqlWalker::HYDRATE_OBJECT_TRANSLATION);

        $this->choiceList = $this->factory->createListFromChoices($entities, $value);

        return $this->choiceList;
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoicesForValues(array $values, $value = null)
    {
        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    /**
     * {@inheritdoc}
     */
    public function loadValuesForChoices(array $choices, $value = null)
    {
        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

    private function resolveQueryBuilder(): QueryBuilder
    {
        if (null === $this->queryBuilder) {
            return $this->doctrine->getRepository($this->className)->createQueryBuilder('e');
        }

        if ($this->queryBuilder instanceof \Closure) {
            return \call_user_func($this->queryBuilder, $this->doctrine->getRepository($this->className));
        }

        return $this->queryBuilder;
    }
}
