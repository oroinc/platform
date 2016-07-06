<?php

namespace Oro\Bundle\OrganizationBundle\Form\Transformer;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Symfony\Component\Form\DataTransformerInterface;

class BusinessUnitTreeTransformer implements DataTransformerInterface
{
    /** @var BusinessUnitManager */
    protected $manager;

    /** @var BusinessUnit */
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
        if (null == $value) {
            return 0;
        } elseif (is_array($value)) {
            foreach($value as &$val) {
                if ($val === "") {
                    $val =0;
                }
            }

            return $this->manager->getBusinessUnitRepo()->findBy(['id' => $value]);
        }

        return $this->manager->getBusinessUnitRepo()->find($value);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null == $value) {
            return 0;
        }

        if (is_array($value) || (is_object($value) && ($value instanceof Collection))) {
            $result = [];
            foreach ($value as $object) {
                $result[] = $object->getId();
            }
        } elseif (is_object($value)) {
            $result = $value->getId();
        } else {
            $result = $value;
        }

        return $result;
    }
}
