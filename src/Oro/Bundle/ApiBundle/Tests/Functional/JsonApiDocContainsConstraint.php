<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;

/**
 * Constraint that asserts that JSON:API document contains an expected JSON:API document.
 */
class JsonApiDocContainsConstraint extends ArrayContainsConstraint
{
    private bool $strictPrimaryData;

    /**
     * @param array $expected          The expected array
     * @param bool  $strict            Whether the order of elements in an array is important
     * @param bool  $strictPrimaryData Whether the order of elements in the primary data is important
     */
    public function __construct(array $expected, bool $strict = true, bool $strictPrimaryData = true)
    {
        parent::__construct($expected, $strict);
        $this->strictPrimaryData = $strictPrimaryData;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    protected function matches($other): bool
    {
        if (parent::matches($other)
            && \is_array($this->expected)
            && \is_array($other)
            && \array_key_exists(JsonApiDoc::DATA, $this->expected)
            && \array_key_exists(JsonApiDoc::DATA, $other)
        ) {
            // test the primary data collection count and order
            $expectedData = $this->expected[JsonApiDoc::DATA];
            if (\is_array($expectedData)) {
                if (empty($expectedData)) {
                    \PHPUnit\Framework\Assert::assertSame($this->expected, $other);
                }
                if (isset($expectedData[0][JsonApiDoc::TYPE])) {
                    $expectedItems = $this->getDataItems($expectedData);
                    $actualItems = $this->getDataItems($other[JsonApiDoc::DATA]);
                    if (!$this->strictPrimaryData) {
                        $this->sortDataForNotStrictComparison($expectedItems);
                        $this->sortDataForNotStrictComparison($actualItems);
                    }
                    try {
                        \PHPUnit\Framework\Assert::assertSame(
                            $expectedItems,
                            $actualItems,
                            sprintf(
                                'Failed asserting the primary data collection items count%s.',
                                $this->strictPrimaryData ? ' and order' : ''
                            )
                        );
                    } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
                        $this->errors[] = [
                            [JsonApiDoc::DATA],
                            $e->getMessage() . PHP_EOL . $e->getComparisonFailure()?->toString()
                        ];

                        return false;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    #[\Override]
    protected function matchAssocArray(array $expected, array $actual, array $path): void
    {
        parent::matchAssocArray($expected, $actual, $path);
        // test links count
        if (\count($path) > 0 && JsonApiDoc::LINKS === $path[\count($path) - 1]) {
            $expectedLinks = array_keys($expected);
            $actualLinks = array_keys($actual);
            sort($expectedLinks);
            sort($actualLinks);
            try {
                \PHPUnit\Framework\Assert::assertSame(
                    $expectedLinks,
                    $actualLinks,
                    'Failed asserting links count.'
                );
            } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
                $this->errors[] = [$path, $e->getMessage()];
            }
        }
    }

    #[\Override]
    protected function matchIndexedArray(array $expected, array $actual, array $path): void
    {
        parent::matchIndexedArray($expected, $actual, $path);

        // test items count for array attributes and to-many relationship
        $indexOfLastPathItem = \count($path) - 1;
        if ($indexOfLastPathItem >= 2
            && (
                JsonApiDoc::ATTRIBUTES === $path[$indexOfLastPathItem - 1]
                || (
                    $indexOfLastPathItem >= 3
                    && JsonApiDoc::DATA === $path[$indexOfLastPathItem]
                    && JsonApiDoc::RELATIONSHIPS === $path[$indexOfLastPathItem - 2]
                )
            )
        ) {
            try {
                \PHPUnit\Framework\Assert::assertCount(\count($expected), $actual, 'Failed asserting items count.');
            } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
                $this->errors[] = [$path, $e->getMessage()];
            }
        }
    }

    /**
     * @param array $data
     *
     * @return array [['type' => entity type, 'id' => entity id], ...]
     */
    private function getDataItems(array $data): array
    {
        $result = [];
        foreach ($data as $item) {
            $result[] = [
                JsonApiDoc::TYPE => $item[JsonApiDoc::TYPE],
                JsonApiDoc::ID   => $item[JsonApiDoc::ID]
            ];
        }

        return $result;
    }

    private function sortDataForNotStrictComparison(array &$data): void
    {
        $sortingData = [];
        foreach ([JsonApiDoc::TYPE, JsonApiDoc::ID] as $fieldName) {
            $sortingItems = array_column($data, $fieldName);
            if ($sortingItems) {
                $sortingData[] = array_column($data, $fieldName);
                $sortingData[] = SORT_ASC;
            }
        }
        if ($sortingData) {
            $sortingData[] = &$data;
            array_multisort(...$sortingData);
        }
    }
}
