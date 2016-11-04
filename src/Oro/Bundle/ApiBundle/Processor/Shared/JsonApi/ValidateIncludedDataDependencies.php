<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

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

        $checkedItems = [];
        $toCheckItems = [];

        // find included entities that have direct relationship with the primary entity
        $primaryObject = $requestData[JsonApiDoc::DATA];
        $includedData = $requestData[JsonApiDoc::INCLUDED];
        foreach ($includedData as $index => $object) {
            $objectKey = $this->getObjectKey($object);
            if ($this->isDependentObject($primaryObject, $objectKey)) {
                $checkedItems[$objectKey] = $index;
            } else {
                $toCheckItems[$objectKey] = $index;
            }
        }

        // check whether the rest of included entities have indirect relationship with the primary entity
        $hasChanges = true;
        while ($hasChanges && !empty($toCheckItems)) {
            $hasChanges = false;
            $keys = array_keys($toCheckItems);
            foreach ($keys as $objectKey) {
                foreach ($checkedItems as $index) {
                    if ($this->isDependentObject($includedData[$index], $objectKey)) {
                        unset($toCheckItems[$objectKey]);
                        $checkedItems[$objectKey] = $index;
                        $hasChanges = true;
                        break;
                    }
                }
            }
        }

        foreach ($toCheckItems as $index) {
            $context->addError($this->createValidationError($index));
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
            $data = $relationship[JsonApiDoc::DATA];
            if (empty($data)) {
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
        );
        $error->setSource(
            ErrorSource::createByPointer(sprintf('/%s/%s', JsonApiDoc::INCLUDED, $includedObjectIndex))
        );

        return $error;
    }
}
