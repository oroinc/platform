<?php

namespace Oro\Bundle\TranslationBundle\Form\ChoiceList;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * Implementation of ChoiceLoaderInterface for TranslatableEntityType.
 * It loads all entities of a given class name or entities restricted by query builder to use for choices.
 */
class TranslationChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @var ChoiceListInterface
     */
    private $choiceList;

    /**
     * @var string
     */
    private $className;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var QueryBuilder|null
     */
    private $queryBuilder;

    /**
     * @var ChoiceListFactoryInterface
     */
    private $factory;

    /**
     * @param string $className
     * @param ManagerRegistry $registry
     * @param ChoiceListFactoryInterface $factory
     * @param QueryBuilder|null $queryBuilder
     */
    public function __construct(
        string $className,
        ManagerRegistry $registry,
        ChoiceListFactoryInterface $factory,
        $queryBuilder
    ) {
        $this->className = $className;
        $this->queryBuilder = $queryBuilder;
        $this->registry = $registry;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoiceList($value = null)
    {
        if ($this->choiceList) {
            return $this->choiceList;
        }

        /** @var $entityManager EntityManager */
        $entityManager = $this->registry->getManager();

        // translation must not be selected separately for each entity
        $entityManager->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'
        );

        // make entity translatable
        $query = $this->resolveQueryBuilder()->getQuery();
        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );

        // In case we use not standard Hydrator (not Query::HYDRATE_OBJECT)
        // we should add this hint to load nested entities
        // otherwise Doctrine will create partial object
        $query->setHint(Query::HINT_INCLUDE_META_COLUMNS, true);

        $entities = $query->execute(null, TranslationWalker::HYDRATE_OBJECT_TRANSLATION);

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

    /**
     * @return QueryBuilder
     */
    private function resolveQueryBuilder()
    {
        if ($this->queryBuilder === null) {
            $repository = $this->registry->getRepository($this->className);
            return $repository->createQueryBuilder('e');
        }

        if ($this->queryBuilder instanceof \Closure) {
            return \call_user_func($this->queryBuilder, $this->registry->getRepository($this->className));
        }

        return $this->queryBuilder;
    }
}
