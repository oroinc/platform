<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Custom data transformer for OroEntityCreateOrSelectType
 */
class EntityCreateOrSelectTransformer implements DataTransformerInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $className;

    /** @var string */
    protected $defaultMode;

    /** @var bool */
    protected $editable;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $className
     * @param string $defaultMode
     * @param bool $editable
     */
    public function __construct(DoctrineHelper $doctrineHelper, $className, $defaultMode, $editable = false)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->className = $className;
        $this->defaultMode = $defaultMode;
        $this->editable = $editable;
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        if ($value !== null && !is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        $newEntity = null;
        $existingEntity = null;
        $mode = $this->defaultMode;

        if ($value) {
            $identifier = $this->doctrineHelper->getSingleEntityIdentifier($value);
            if ($identifier) {
                $existingEntity = $value;
                $mode = $this->editable ?
                    OroEntityCreateOrSelectType::MODE_EDIT :
                    OroEntityCreateOrSelectType::MODE_VIEW;
                if ($mode === OroEntityCreateOrSelectType::MODE_EDIT) {
                    $newEntity = $value;
                }
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function reverseTransform($value)
    {
        if ($value !== null && !is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        if ($value === null) {
            return null;
        }

        if (!array_key_exists('mode', $value)) {
            throw new TransformationFailedException('Data parameter "mode" is required');
        }

        $entity = null;
        switch ($value['mode']) {
            case OroEntityCreateOrSelectType::MODE_CREATE:
                if (!array_key_exists('new_entity', $value)) {
                    throw new TransformationFailedException('Data parameter "new_entity" is required');
                }
                $entity = $value['new_entity'];
                break;

            case OroEntityCreateOrSelectType::MODE_VIEW:
            case OroEntityCreateOrSelectType::MODE_EDIT:
                if (!array_key_exists('existing_entity', $value)) {
                    throw new TransformationFailedException('Data parameter "existing_entity" is required');
                }
                $entity = $value['existing_entity'];
                break;
        }

        return $entity;
    }
}
