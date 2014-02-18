<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectType;

/**
 * Custom data transformer for OroEntityCreateOrSelectType
 */
class EntityCreateOrSelectTransformer implements DataTransformerInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $defaultMode;

    public function __construct(DoctrineHelper $doctrineHelper, $className, $defaultMode)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->className = $className;
        $this->defaultMode = $defaultMode;
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        // default form values
        $newEntity = null;
        $existingEntity = null;
        $mode = $this->defaultMode;

        if ($value) {
            $identifier = $this->doctrineHelper->getSingleEntityIdentifier($value);
            if ($identifier) {
                $existingEntity = $value;
                $mode = OroEntityCreateOrSelectType::MODE_VIEW;
            } else {
                $newEntity = $value;
                $mode = OroEntityCreateOrSelectType::MODE_CREATE;
            }
        }

        return array(
            'new_entity' => $newEntity,
            'existing_entity' => $existingEntity,
            'mode' => $mode
        );
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }
}
