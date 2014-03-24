<?php

namespace Oro\Bundle\OrganizationBundle\Form\Transformer;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Symfony\Component\Form\DataTransformerInterface;

class BusinessUnitTreeTransformer implements DataTransformerInterface
{
    /**
     * @var BusinessUnitManager
     */
    protected $manager;

    /**
     * @var BusinessUnit
     */
    protected $entity;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        return $this->manager->getBusinessUnitRepo()
            ->findBy(['id' => $value]);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        $result = [];
        foreach ($value as $object) {
            $result[] = $object->getId();
        }

        return $result;
    }
}
