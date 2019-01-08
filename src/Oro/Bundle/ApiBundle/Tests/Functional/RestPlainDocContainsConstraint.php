<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\Rest\RestDocumentBuilder as RestApiDoc;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;

/**
 * Constraint that asserts that plain REST API document contains an expected document.
 */
class RestPlainDocContainsConstraint extends ArrayContainsConstraint
{
    /**
     * {@inheritdoc}
     */
    protected function matches($other)
    {
        if (parent::matches($other)
            && is_array($this->expected)
            && is_array($other)
            && !empty($this->expected)
            && !ArrayUtil::isAssoc($this->expected)
            && count($this->expected) !== count($other)
        ) {
            try {
                \PHPUnit\Framework\Assert::assertCount(
                    count($this->expected),
                    $other,
                    'Failed asserting the primary data collection items count.'
                );
            } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
                $this->errors[] = [[], $e->getMessage()];

                return false;
            }
        }

        return empty($this->errors);
    }

    /**
     * {@inheritdoc}
     */
    protected function matchAssocArray(array $expected, array $actual, array $path)
    {
        parent::matchAssocArray($expected, $actual, $path);
        // test links count
        if (count($path) > 0 && RestApiDoc::LINKS === $path[count($path) - 1]) {
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
}
