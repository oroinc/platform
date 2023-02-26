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
    protected const FIELD_OPTIONS_KEY = 'field_options';
    protected const CHOICES_KEY = 'choices';

    protected ManagerRegistry $doctrine;

    public function __construct(FormFactoryInterface $factory, FilterUtility $util, ManagerRegistry $doctrine)
    {
        parent::__construct($factory, $util);
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'choice';
        $options = $this->getOr(FilterUtility::FORM_OPTIONS_KEY, []);
        if ($this->isLazy()
            && isset($options[self::FIELD_OPTIONS_KEY])
            && !isset($options[self::FIELD_OPTIONS_KEY][self::CHOICES_KEY])
        ) {
            $this->params[FilterUtility::FORM_OPTIONS_KEY][self::FIELD_OPTIONS_KEY][self::CHOICES_KEY] = [];
            $this->additionalOptions[] = [self::FIELD_OPTIONS_KEY, self::CHOICES_KEY];
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
     * {@inheritDoc}
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

        return $options[self::FIELD_OPTIONS_KEY]['class'] ?? null;
    }

    protected function getEntity(string $entityClass, mixed $entityId, bool $forceEntityLoad = false): ?object
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
