<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by an entity.
 * The entity class is specified in the options -> field_options -> class parameter.
 */
class EntityFilter extends ChoiceFilter
{
    /** @var ManagerRegistry */
    protected $doctrine;

    public function __construct(FormFactoryInterface $factory, FilterUtility $util, ManagerRegistry $doctrine)
    {
        parent::__construct($factory, $util);
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'choice';
        $options = $this->getOr(FilterUtility::FORM_OPTIONS_KEY, []);
        if ($this->isLazy() && isset($options['field_options']) && !isset($options['field_options']['choices'])) {
            $this->params[FilterUtility::FORM_OPTIONS_KEY]['field_options']['choices'] = [];
            $this->additionalOptions[] = ['field_options', 'choices'];
        }

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        if (isset($data['value'])) {
            $entityClass = $this->getEntityClass();
            if ($entityClass) {
                $value = $data['value'];
                if (\is_array($value)) {
                    $entities = [];
                    foreach ($value as $val) {
                        $entity = $this->getEntity($entityClass, $val);
                        if (null !== $entity) {
                            $entities[] = $entity;
                        }
                    }
                    $data['value'] = $entities;
                } else {
                    $data['value'] = $this->getEntity($entityClass, $value);
                }
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return EntityFilterType::class;
    }

    protected function getEntityClass(): ?string
    {
        $options = $this->getOr(FilterUtility::FORM_OPTIONS_KEY);
        if (!$options) {
            return null;
        }

        return $options['field_options']['class'] ?? null;
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     * @param bool   $forceEntityLoad
     *
     * @return object|null
     */
    protected function getEntity(string $entityClass, $entityId, bool $forceEntityLoad = false)
    {
        $em = $this->doctrine->getManagerForClass($entityClass);
        if (!$em instanceof EntityManagerInterface) {
            return null;
        }

        return $forceEntityLoad
            ? $em->find($entityClass, $entityId)
            : $em->getReference($entityClass, $entityId);
    }
}
