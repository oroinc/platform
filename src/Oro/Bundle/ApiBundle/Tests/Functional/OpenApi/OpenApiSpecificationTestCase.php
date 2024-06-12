<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\OpenApi;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;

class OpenApiSpecificationTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    protected function assertOpenApiSpecificationEquals(array $expected, array $actual): void
    {
        $expected = $this->prepareExpectedData($expected);
        self::assertThat($actual, new ArrayContainsConstraint($expected, true));
    }

    /**
     * Updates expected data to be able to use them in all types of applications.
     */
    private function prepareExpectedData(array $expected): array
    {
        $apiUrlPrefix = substr($this->getUrl('oro_rest_api_list', ['entity' => 'test']), 0, -4);
        $paths = [];
        foreach ($expected['paths'] as $url => $item) {
            $apiUrlPrefixPos = strpos($url, $apiUrlPrefix);
            if ($apiUrlPrefixPos > 0) {
                $url = substr($url, $apiUrlPrefixPos);
            }
            $paths[$url] = $item;
        }
        $expected['paths'] = $paths;

        return $expected;
    }
}
