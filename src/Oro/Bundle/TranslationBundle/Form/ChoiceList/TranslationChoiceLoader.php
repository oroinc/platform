<?php

namespace Oro\Bundle\TranslationBundle\Form\ChoiceList;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

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
     * @var ChoiceListFactoryInterface
     */
    private $factory;

    /**
     * @param string $className
     * @param ManagerRegistry $registry
     * @param ChoiceListFactoryInterface $factory
     */
    public function __construct(
        string $className,
        ManagerRegistry $registry,
        ChoiceListFactoryInterface $factory
    ) {
        $this->className = $className;
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

        // get query builder
        if (!empty($options['query_builder'])) {
            $queryBuilder = $options['query_builder'];
            if ($queryBuilder instanceof \Closure) {
                $queryBuilder = $queryBuilder($this->registry->getRepository($this->className));
            }
        } else {
            /** @var $repository EntityRepository */
            $repository = $this->registry->getRepository($this->className);
            $queryBuilder = $repository->createQueryBuilder('e');
        }

        // translation must not be selected separately for each entity
        $entityManager->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'
        );

        // make entity translatable
        /** @var $queryBuilder QueryBuilder */
        $query = $queryBuilder->getQuery();
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
}
