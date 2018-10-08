<?php

namespace Oro\Bundle\TranslationBundle\Form\ChoiceList;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Translation\TranslatableQueryTrait;
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

    /** @var ChoiceListInterface */
    private $choiceList;

    /** @var string */
    private $className;

    /** @var ManagerRegistry */
    private $registry;

    /** @var QueryBuilder|null */
    private $queryBuilder;

    /** @var ChoiceListFactoryInterface */
    private $factory;

    /** @var AclHelper */
    private $aclHelper;

    /** @var array [check => bool, permission => permission_name, options => [option_name => option_value, ...]] */
    private $aclOptions;

    /**
     * @param string $className
     * @param ManagerRegistry $registry
     * @param ChoiceListFactoryInterface $factory
     * @param QueryBuilder|null $queryBuilder
     * @param AclHelper|null $aclHelper
     * @param array $aclOptions
     */
    public function __construct(
        string $className,
        ManagerRegistry $registry,
        ChoiceListFactoryInterface $factory,
        $queryBuilder,
        AclHelper $aclHelper = null,
        array $aclOptions = []
    ) {
        $this->className = $className;
        $this->queryBuilder = $queryBuilder;
        $this->registry = $registry;
        $this->factory = $factory;
        $this->aclHelper = $aclHelper;
        $this->aclOptions = $aclOptions;
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
        $this->addTranslatableLocaleHint($query, $entityManager);

        // In case we use not standard Hydrator (not Query::HYDRATE_OBJECT)
        // we should add this hint to load nested entities
        // otherwise Doctrine will create partial object
        $query->setHint(Query::HINT_INCLUDE_META_COLUMNS, true);

        // Protects the query with ACL.
        if ($this->aclHelper && (!isset($this->aclOptions['disable']) || true !== $this->aclOptions['disable'])) {
            $options = isset($this->aclOptions['options']) ? $this->aclOptions['options'] : [];
            $permission = isset($this->aclOptions['permission']) ? $this->aclOptions['permission'] : 'VIEW';
            $this->aclHelper->apply($query, $permission, $options);
        }

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
