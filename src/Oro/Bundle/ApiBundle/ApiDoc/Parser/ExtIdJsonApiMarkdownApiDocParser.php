<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\RequestAwareResourceDocParserInterface;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Extracts documentation for API resources using the provided parser and do the following modifications:
 * * adds "id" field to JSON:API examples of the "create" action
 * * replaces a value of "id" field in JSON:API examples of the "update" action
 * * removes unneeded attributes from JSON:API examples of the "create" and "update" actions
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExtIdJsonApiMarkdownApiDocParser implements ResourceDocParserInterface, RequestAwareResourceDocParserInterface
{
    private const REQUEST_START_TAG = '{@request:json_api}';
    private const REQUEST_END_TAG = '{@/request}';
    private const JSON_START_TAG = '<code class="JSON">';
    private const JSON_END_TAG = '</code>';

    private RequestType $requestType;
    private array $idValues = [];
    private array $attributesToRemove = [];
    private array $attributesToRemovePatterns = [];

    public function __construct(
        private readonly ResourceDocParserInterface $resourceDocParser,
        private readonly array $extIdEntities,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly ValueNormalizer $valueNormalizer
    ) {
    }

    public function setIdValue(string $className, string $idValue): void
    {
        $this->idValues[$className] = $idValue;
    }

    public function setAttributesToRemove(string $className, array $attributeNames): void
    {
        $this->attributesToRemove[$className] = $attributeNames;
    }

    #[\Override]
    public function setRequestType(RequestType $requestType): void
    {
        $this->requestType = $requestType;
    }

    #[\Override]
    public function getActionDocumentation(string $className, string $actionName): ?string
    {
        $text = $this->resourceDocParser->getActionDocumentation($className, $actionName);
        if (
            $text
            && (ApiAction::CREATE === $actionName || ApiAction::UPDATE === $actionName)
            && $this->isExtIdResource($className)
        ) {
            $offset = 0;
            $endOffset = 0;
            while ($this->gotoRequestJsonApiBlock($text, $offset, $endOffset)) {
                $endRequestBlockOffset = $endOffset;
                while ($this->gotoJsonBlock($text, $offset, $endOffset, $endRequestBlockOffset)) {
                    $text = $this->modifyJsonBlock($actionName, $className, $text, $offset, $endOffset);
                    $offset = $endOffset + \strlen(self::JSON_END_TAG) + 1;
                }
                $offset = $endOffset + \strlen(self::REQUEST_END_TAG) + 1;
            }
        }

        return $text;
    }

    #[\Override]
    public function getFieldDocumentation(string $className, string $fieldName, ?string $actionName = null): ?string
    {
        return $this->resourceDocParser->getFieldDocumentation($className, $fieldName, $actionName);
    }

    #[\Override]
    public function getFilterDocumentation(string $className, string $filterName): ?string
    {
        return $this->resourceDocParser->getFilterDocumentation($className, $filterName);
    }

    #[\Override]
    public function getSubresourceDocumentation(string $className, string $subresourceName, string $actionName): ?string
    {
        return $this->resourceDocParser->getSubresourceDocumentation($className, $subresourceName, $actionName);
    }

    #[\Override]
    public function registerDocumentationResource(string $resource): bool
    {
        return $this->resourceDocParser->registerDocumentationResource($resource);
    }

    private function isExtIdResource(string $className): bool
    {
        if (isset($this->extIdEntities[$className])) {
            return true;
        }

        if ($this->isInheritanceMappingEntity($className)) {
            foreach ($this->extIdEntities as $class => $field) {
                if (is_a($className, $class, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isInheritanceMappingEntity(string $className): bool
    {
        return
            $this->doctrineHelper->isManageableEntityClass($className)
            && !$this->doctrineHelper->getEntityMetadataForClass($className)->isInheritanceTypeNone();
    }

    private function getEntityType(string $className): string
    {
        return ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $className, $this->requestType);
    }

    private function getAttributesToRemovePatterns(string $className): array
    {
        $attributesToRemove = $this->attributesToRemove[$className] ?? null;
        if (null === $attributesToRemove) {
            $attributesToRemovePatterns = $this->attributesToRemovePatterns[''] ?? null;
            if (null === $attributesToRemovePatterns) {
                $attributesToRemovePatterns = [' "externalId":'];
                $this->attributesToRemovePatterns[''] = $attributesToRemovePatterns;
            }

            return $attributesToRemovePatterns;
        }

        $attributesToRemovePatterns = $this->attributesToRemovePatterns[$className] ?? null;
        if (null === $attributesToRemovePatterns) {
            $attributesToRemovePatterns = [];
            foreach ($attributesToRemove as $attribute) {
                $attributesToRemovePatterns[] = ' "' . $attribute . '":';
            }
            $this->attributesToRemovePatterns[$className] = $attributesToRemovePatterns;
        }

        return $attributesToRemovePatterns;
    }

    private function modifyJsonBlock(
        string $actionName,
        string $className,
        string $text,
        int &$offset,
        int &$endOffset
    ): string {
        if (!str_contains($this->readNextNotEmptyLine($text, $offset, $endOffset), '{')) {
            return $text;
        }
        if (!str_contains($this->readNextNotEmptyLine($text, $offset, $endOffset), '"data":')) {
            return $text;
        }

        return ApiAction::CREATE === $actionName
            ? $this->modifyJsonBlockForCreateAction($className, $text, $offset, $endOffset)
            : $this->modifyJsonBlockForUpdateAction($className, $text, $offset, $endOffset);
    }

    private function modifyJsonBlockForCreateAction(
        string $className,
        string $text,
        int &$offset,
        int &$endOffset
    ): string {
        $typeLine = $this->readNextNotEmptyLine($text, $offset, $endOffset);
        if (!str_contains($typeLine, ' "type":') || $this->getType($typeLine) !== $this->getEntityType($className)) {
            return $text;
        }

        $currentLineOffset = $offset;
        $currentLine = $this->readNextNotEmptyLine($text, $offset, $endOffset);
        if (str_contains($currentLine, ' "id":')) {
            $currentLine = $this->readNextNotEmptyLine($text, $offset, $endOffset);
        } else {
            $idLine = $this->getIdLine($className, substr($typeLine, 0, strpos($typeLine, '"type":')));
            $text = substr($text, 0, $currentLineOffset) . $idLine . substr($text, $currentLineOffset);
            $offset += \strlen($idLine);
        }
        if (str_contains($currentLine, ' "attributes":')) {
            $text = $this->removeUnneededAttributes($className, $text, $offset, $endOffset, $currentLine);
        }

        return $text;
    }

    private function modifyJsonBlockForUpdateAction(
        string $className,
        string $text,
        int &$offset,
        int &$endOffset
    ): string {
        $typeLine = $this->readNextNotEmptyLine($text, $offset, $endOffset);
        if (!str_contains($typeLine, ' "type":') || $this->getType($typeLine) !== $this->getEntityType($className)) {
            return $text;
        }

        $currentLineOffset = $offset;
        $currentLine = $this->readNextNotEmptyLine($text, $offset, $endOffset);
        if (str_contains($currentLine, ' "id":')) {
            $idValue = $this->idValues[$className] ?? null;
            if ($idValue) {
                $text = $this->replaceIdValue($text, $idValue, $offset, $endOffset, $currentLineOffset);
            }
            $currentLine = $this->readNextNotEmptyLine($text, $offset, $endOffset);
        }
        if (str_contains($currentLine, ' "attributes":')) {
            $text = $this->removeUnneededAttributes($className, $text, $offset, $endOffset, $currentLine);
        }

        return $text;
    }

    private function getType(string $typeLine): ?string
    {
        $startValueOffset = strpos($typeLine, '"', strpos($typeLine, '"type":') + 7);
        if (false === $startValueOffset) {
            return null;
        }

        $startValueOffset++;
        $endValueOffset = strpos($typeLine, '"', $startValueOffset);
        if (false === $endValueOffset) {
            return null;
        }

        return substr($typeLine, $startValueOffset, $endValueOffset - $startValueOffset);
    }

    private function replaceIdValue(
        string $text,
        string $idValue,
        int &$offset,
        int &$endOffset,
        int $lineOffset
    ): string {
        $startValueOffset = strpos($text, '"', strpos($text, '"id":', $lineOffset) + 5);
        if (false === $startValueOffset) {
            return $text;
        }

        $startValueOffset++;
        $endValueOffset = strpos($text, '"', $startValueOffset);
        if (false === $endValueOffset) {
            return $text;
        }

        $length = ($endValueOffset - $startValueOffset) - \strlen($idValue);
        $offset -= $length;
        $endOffset -= $length;

        return substr($text, 0, $startValueOffset) . $idValue . substr($text, $endValueOffset);
    }

    private function removeUnneededAttributes(
        string $className,
        string $text,
        int &$offset,
        int &$endOffset,
        string $startAttributesLine
    ): string {
        $endAttributesLine = substr($startAttributesLine, 0, strpos($startAttributesLine, '"attributes":')) . '}';
        $attributesToRemovePatterns = $this->getAttributesToRemovePatterns($className);
        while ($offset < $endOffset) {
            $startOffset = $offset;
            $line = $this->readNextNotEmptyLine($text, $offset, $endOffset);
            if (str_starts_with($line, $endAttributesLine)) {
                break;
            }
            if ($this->isUnneededAttribute($line, $attributesToRemovePatterns)) {
                $text = substr($text, 0, $startOffset) . substr($text, $offset);
                $length = $offset - $startOffset;
                $offset -= $length;
                $endOffset -= $length;
            }
        }

        return $text;
    }

    private function isUnneededAttribute(string $line, array $attributesToRemovePatterns): bool
    {
        foreach ($attributesToRemovePatterns as $pattern) {
            if (str_contains($line, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function getIdLine(string $className, string $prefix): string
    {
        return $prefix . \sprintf("\"id\": \"%s\",\n", $this->idValues[$className] ?? '1');
    }

    private function gotoRequestJsonApiBlock(string $text, int &$offset, int &$endOffset): bool
    {
        return $this->gotoBlock($text, $offset, $endOffset, self::REQUEST_START_TAG, self::REQUEST_END_TAG);
    }

    private function gotoJsonBlock(string $text, int &$offset, int &$endOffset, int $stopSearchOffset): bool
    {
        return $this->gotoBlock(
            $text,
            $offset,
            $endOffset,
            self::JSON_START_TAG,
            self::JSON_END_TAG,
            $stopSearchOffset
        );
    }

    private function gotoBlock(
        string $text,
        int &$offset,
        int &$endOffset,
        string $startTag,
        string $endTag,
        ?int $stopSearchOffset = null
    ): bool {
        $blockStartOffset = strpos($text, $startTag, $offset);
        if (false === $blockStartOffset || (null !== $stopSearchOffset && $blockStartOffset >= $stopSearchOffset)) {
            return false;
        }

        $blockEndOffset = strpos($text, $endTag, $blockStartOffset);
        if (false === $blockEndOffset || (null !== $stopSearchOffset && $blockEndOffset >= $stopSearchOffset)) {
            return false;
        }

        $offset = $blockStartOffset + \strlen($startTag);
        $endOffset = $blockEndOffset;

        return true;
    }

    private function readNextNotEmptyLine(string $text, int &$offset, int $endOffset): string
    {
        $eolOffset = strpos($text, "\n", $offset);
        if (false === $eolOffset) {
            $line = substr($text, $offset, $endOffset - $offset);
            $offset = $endOffset;

            return $line;
        }

        $line = substr($text, $offset, $eolOffset - $offset);
        $offset = $eolOffset + 1;
        if (!$line) {
            $line = $this->readNextNotEmptyLine($text, $offset, $endOffset);
        }

        return $line;
    }
}
