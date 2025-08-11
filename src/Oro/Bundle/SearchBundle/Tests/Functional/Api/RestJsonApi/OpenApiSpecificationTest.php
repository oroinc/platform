<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\OpenApi\OpenApiSpecificationTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @group regression
 */
class OpenApiSpecificationTest extends OpenApiSpecificationTestCase
{
    public function testValidateGeneratedOpenApiSpecificationForEntityWithSearchFilter(): void
    {
        $params = ['--view' => 'rest_json_api', '--format' => 'yaml', '--entity=businessunits'];
        $result = self::runCommand('oro:api:doc:open-api:dump', $params, false, true);
        $fileName = __DIR__ . '/data/rest_json_api_entity_with_search_filter.yml';
        $expected = Yaml::parse(file_get_contents($fileName));
        $actual = Yaml::parse($result);
        $this->assertOpenApiSpecificationEquals($expected, $actual);
    }
}
