<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Makes sure that "included" section of the request data contains only
 * entities that have a relationship with the primary entity.
 */
class ValidateIncludedDataDependencies implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $requestData = $context->getRequestData();
        if (empty($requestData[JsonApiDoc::INCLUDED])) {
            // there are no included data in the request
            return;
        }
        if (!array_key_exists(JsonApiDoc::DATA, $requestData)) {
            throw new \LogicException(
                sprintf('The "%s" section must exist in the request data.', JsonApiDoc::DATA)
            );
        }

        $primaryObject = $requestData[JsonApiDoc::DATA];
        $includedData = $requestData[JsonApiDoc::INCLUDED];
        if (!is_array($primaryObject) || !is_array($includedData)) {
            // invalid primary data or included data in the request
            return;
        }

        $checked = [];
        $toCheck = [];

        // find included entities that have direct relationship with the primary entity
        $this->processDirectRelationships($checked, $toCheck, $includedData, $primaryObject);
        // find included entities that have direct link to the primary entity
        if (!empty($toCheck)) {
            $primaryObjectKey = $this->getObjectKey($primaryObject);
            if ($primaryObjectKey) {
                $this->processDirectInverseRelationships($checked, $toCheck, $includedData, $primaryObjectKey);
            }
        }
        // check whether the rest of included entities have indirect relationship with the primary entity
        if (!empty($toCheck)) {
            $this->processIndirectRelationships($checked, $toCheck, $includedData);
        }

        foreach ($toCheck as $index) {
            $context->addError($this->createValidationError($index));
        }
    }

    /**
     * @param array $checked
     * @param array $toCheck
     * @param array $includedData
     * @param array $primaryObject
     */
    protected function processDirectRelationships(
        array &$checked,
        array &$toCheck,
        array $includedData,
        array $primaryObject
    ) {
        foreach ($includedData as $index => $object) {
            $objectKey = $this->getObjectKey($object);
            if ($this->isDependentObject($primaryObject, $objectKey)) {
                $checked[$objectKey] = $index;
            } else {
                $toCheck[$objectKey] = $index;
            }
        }
    }

    /**
     * @param array  $checked
     * @param array  $toCheck
     * @param array  $includedData
     * @param string $primaryObjectKey
     */
    protected function processDirectInverseRelationships(
        array &$checked,
        array &$toCheck,
        array $includedData,
        $primaryObjectKey
    ) {
        $keys = array_keys($toCheck);
        foreach ($keys as $objectKey) {
            $index = $toCheck[$objectKey];
            if ($this->isDependentObject($includedData[$index], $primaryObjectKey)) {
                unset($toCheck[$objectKey]);
                $checked[$objectKey] = $index;
                break;
            }
        }
    }

    /**
     * @param array $checked
     * @param array $toCheck
     * @param array $includedData
     */
    protected function processIndirectRelationships(array &$checked, array &$toCheck, array $includedData)
    {
        $hasChanges = true;
        while ($hasChanges && !empty($toCheck)) {
            $hasChanges = false;
            $keys = array_keys($toCheck);
            foreach ($keys as $objectKey) {
                $objectIndex = $toCheck[$objectKey];
                foreach ($checked as $checkedObjectKey => $checkedObjectIndex) {
                    if ($this->isDependentObject($includedData[$checkedObjectIndex], $objectKey)
                        || $this->isDependentObject($includedData[$objectIndex], $checkedObjectKey)
                    ) {
                        unset($toCheck[$objectKey]);
                        $checked[$objectKey] = $objectIndex;
                        $hasChanges = true;
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param array  $object
     * @param string $targetObjectKey
     *
     * @return bool
     */
    protected function isDependentObject(array $object, $targetObjectKey)
    {
        if (empty($object[JsonApiDoc::RELATIONSHIPS])) {
            return false;
        }

        foreach ($object[JsonApiDoc::RELATIONSHIPS] as $relationship) {
            if (!array_key_exists(JsonApiDoc::DATA, $relationship)) {
                continue;
            }
            $data = $relationship[JsonApiDoc::DATA];
            if (!is_array($data) || empty($data)) {
                continue;
            }
            if (!ArrayUtil::isAssoc($data)) {
                foreach ($data as $item) {
                    if ($this->getObjectKey($item) === $targetObjectKey) {
                        return true;
                    }
                }
            } elseif ($this->getObjectKey($data) === $targetObjectKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array|null $object
     *
     * @return string|null
     */
    protected function getObjectKey($object)
    {
        if (is_array($object)
            && array_key_exists(JsonApiDoc::TYPE, $object)
            && array_key_exists(JsonApiDoc::ID, $object)
        ) {
            return sprintf('%s::%s', $object[JsonApiDoc::TYPE], $object[JsonApiDoc::ID]);
        }

        return null;
    }

    /**
     * @param int $includedObjectIndex
     *
     * @return Error
     */
    protected function createValidationError($includedObjectIndex)
    {
        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'The entity should have a relationship with the primary entity'
            . ' and this should be explicitly specified in the request'
        );
        $error->setSource(
            ErrorSource::createByPointer(sprintf('/%s/%s', JsonApiDoc::INCLUDED, $includedObjectIndex))
        );

        return $error;
    }
}
