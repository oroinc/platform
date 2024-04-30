<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\OpenApi;

use Symfony\Component\Yaml\Yaml;

/**
 * @group regression
 */
class OpenApiTest extends OpenApiTestCase
{
    public function testGenerationOpenApiWhenNoView(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "--view" option is missing.');
        self::runCommand('oro:api:doc:open-api:dump', [], false, true);
    }

    public function testGenerationOpenApiWhenUnknownView(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The specified view does not exist.');
        self::runCommand('oro:api:doc:open-api:dump', ['--view' => 'unknown'], false, true);
    }

    public function testGenerationOpenApiForRestJsonApiSuccess(): void
    {
        $result = self::runCommand('oro:api:doc:open-api:dump', ['--view' => 'rest_json_api'], false, true);
        self::assertStringContainsString('{"openapi":"3.1.0"', $result);
    }

    public function testGenerationOpenApiForRestJsonApiSuccessWithJsonPrettyFormat(): void
    {
        $result = self::runCommand(
            'oro:api:doc:open-api:dump',
            ['--view' => 'test_new_rest_api', '--format' => 'json-pretty'],
            false,
            true
        );
        self::assertStringContainsString("{\n    \"openapi\": \"3.1.0\"", $result);
    }

    public function testGenerationOpenApiForRestJsonApiSuccessWithYamlFormat(): void
    {
        $result = self::runCommand(
            'oro:api:doc:open-api:dump',
            ['--view' => 'test_new_rest_api', '--format' => 'yaml'],
            false,
            true
        );
        self::assertStringContainsString('openapi: 3.1.0', $result);
    }

    public function testGenerationOpenApiForRestJsonApiSuccessWithTitle(): void
    {
        $result = self::runCommand(
            'oro:api:doc:open-api:dump',
            ['--view' => 'test_new_rest_api', '--title' => 'Test API'],
            false,
            true
        );
        self::assertStringContainsString('"title":"Test API"', $result);
    }

    /**
     * @dataProvider validateGeneratedOpenApiForRestJsonApiDataProvider
     */
    public function testValidateGeneratedOpenApiForRestJsonApi(string $fileSuffix, array $entityTypes): void
    {
        $params = ['--view' => 'rest_json_api', '--format' => 'yaml'];
        foreach ($entityTypes as $entityType) {
            $params[] = '--entity=' . $entityType;
        }
        $result = self::runCommand('oro:api:doc:open-api:dump', $params, false, true);
        $fileName = __DIR__ . '/data/rest_json_api_' . $fileSuffix . '.yml';
        $expected = Yaml::parse(file_get_contents($fileName));
        $actual = Yaml::parse($result);
        $this->assertOpenApiEquals($expected, $actual);
    }

    public static function validateGeneratedOpenApiForRestJsonApiDataProvider(): array
    {
        return [
            ['data_types', ['testapialldatatypes']],
            ['collections', ['testapicollections', 'testapicollectionitems']],
            ['custom_identifiers', ['testapicustomidentifiers']],
            ['activities', ['testapiactivities']],
            ['other', ['asyncoperations', 'testapiorders', 'testapiorderlineitems']],
        ];
    }
}
