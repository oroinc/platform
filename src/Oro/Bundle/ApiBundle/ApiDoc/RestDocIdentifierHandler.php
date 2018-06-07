<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Symfony\Component\Routing\Route;

class RestDocIdentifierHandler
{
    const ID_ATTRIBUTE = 'id';

    /** @var RestDocViewDetector */
    private $docViewDetector;

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var ApiDocDataTypeConverter */
    private $dataTypeConverter;

    /**
     * @param RestDocViewDetector     $docViewDetector
     * @param ValueNormalizer         $valueNormalizer
     * @param ApiDocDataTypeConverter $dataTypeConverter
     */
    public function __construct(
        RestDocViewDetector $docViewDetector,
        ValueNormalizer $valueNormalizer,
        ApiDocDataTypeConverter $dataTypeConverter
    ) {
        $this->docViewDetector = $docViewDetector;
        $this->valueNormalizer = $valueNormalizer;
        $this->dataTypeConverter = $dataTypeConverter;
    }

    /**
     * @param ApiDoc         $annotation
     * @param Route          $route
     * @param EntityMetadata $metadata
     * @param string|null    $description
     */
    public function handle(ApiDoc $annotation, Route $route, EntityMetadata $metadata, ?string $description)
    {
        $idFields = $metadata->getIdentifierFieldNames();
        $dataType = DataType::STRING;
        if (\count($idFields) === 1) {
            $field = $metadata->getField(\reset($idFields));
            if (!$field) {
                throw new \RuntimeException(\sprintf(
                    'The metadata for "%s" entity does not contains "%s" identity field. Resource: %s %s',
                    $metadata->getClassName(),
                    \reset($idFields),
                    \implode(' ', $route->getMethods()),
                    $route->getPath()
                ));
            }
            $dataType = $field->getDataType();
        }

        $annotation->addRequirement(
            self::ID_ATTRIBUTE,
            [
                'dataType'    => $this->dataTypeConverter->convertDataType($dataType),
                'requirement' => $this->getIdRequirement($metadata),
                'description' => $description
            ]
        );
    }

    /**
     * @param EntityMetadata $metadata
     *
     * @return string
     */
    private function getIdRequirement(EntityMetadata $metadata)
    {
        $idFields = $metadata->getIdentifierFieldNames();
        $idFieldCount = \count($idFields);
        if ($idFieldCount === 1) {
            // single identifier
            return $this->getIdFieldRequirement($metadata->getField(\reset($idFields))->getDataType());
        }

        // composite identifier
        $requirements = [];
        foreach ($idFields as $field) {
            $requirements[] = $field . '=' . $this->getIdFieldRequirement($metadata->getField($field)->getDataType());
        }

        return \implode(',', $requirements);
    }

    /**
     * @param string $fieldType
     *
     * @return string
     */
    private function getIdFieldRequirement($fieldType)
    {
        $result = $this->valueNormalizer->getRequirement(
            $fieldType,
            $this->docViewDetector->getRequestType()
        );

        if (ValueNormalizer::DEFAULT_REQUIREMENT === $result) {
            $result = '[^\.]+';
        }

        return $result;
    }
}
