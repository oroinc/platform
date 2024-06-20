<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\OpenApi;

use Symfony\Component\Yaml\Yaml;

/**
 * @group regression
 */
class OpenApiSpecificationTest extends OpenApiSpecificationTestCase
{
    public function testGenerationOpenApiSpecificationWhenNoView(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "--view" option is missing.');
        self::runCommand('oro:api:doc:open-api:dump', [], false, true);
    }

    public function testGenerationOpenApiSpecificationWhenUnknownView(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The specified view does not exist.');
        self::runCommand('oro:api:doc:open-api:dump', ['--view' => 'unknown'], false, true);
    }

    public function testGenerationOpenApiSpecificationForRestJsonApiSuccess(): void
    {
        $result = self::runCommand('oro:api:doc:open-api:dump', ['--view' => 'rest_json_api'], false, true);
        self::assertStringContainsString('{"openapi":"3.1.0"', $result);
    }

    public function testGenerationOpenApiSpecificationForRestJsonApiSuccessWithJsonPrettyFormat(): void
    {
        $result = self::runCommand(
            'oro:api:doc:open-api:dump',
            ['--view' => 'test_new_rest_api', '--format' => 'json-pretty'],
            false,
            true
        );
        self::assertStringContainsString("{\n    \"openapi\": \"3.1.0\"", $result);
    }

    public function testGenerationOpenApiSpecificationForRestJsonApiSuccessWithYamlFormat(): void
    {
        $result = self::runCommand(
            'oro:api:doc:open-api:dump',
            ['--view' => 'test_new_rest_api', '--format' => 'yaml'],
            false,
            true
        );
        self::assertStringContainsString('openapi: 3.1.0', $result);
    }

    public function testGenerationOpenApiSpecificationForRestJsonApiSuccessWithTitle(): void
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
     * @dataProvider validateGeneratedOpenApiSpecificationForRestJsonApiDataProvider
     */
    public function testValidateGeneratedOpenApiSpecificationForRestJsonApi(
        string $fileSuffix,
        array $entityTypes
    ): void {
        $params = ['--view' => 'rest_json_api', '--format' => 'yaml'];
        foreach ($entityTypes as $entityType) {
            $params[] = '--entity=' . $entityType;
        }
        $result = self::runCommand('oro:api:doc:open-api:dump', $params, false, true);
        $fileName = __DIR__ . '/data/rest_json_api_' . $fileSuffix . '.yml';
        $expected = Yaml::parse(file_get_contents($fileName));
        $actual = Yaml::parse($result);
        $this->assertOpenApiSpecificationEquals($expected, $actual);
    }

    public static function validateGeneratedOpenApiSpecificationForRestJsonApiDataProvider(): array
    {
        return [
            ['data_types', ['testapialldatatypes']],
            ['collections', ['testapicollections', 'testapicollectionitems']],
            ['custom_identifiers', ['testapicustomidentifiers']],
            ['activities', ['testapiactivities']],
            ['other', ['asyncoperations', 'testapiorders', 'testapiorderlineitems']],
        ];
    }

    public function testGenerationOpenApiSpecificationWithServerUrls(): void
    {
        $result = self::runCommand(
            'oro:api:doc:open-api:dump',
            [
                '--view'   => 'rest_json_api',
                '--entity' => 'asyncoperations',
                '--server-url=http://example1.com',
                '--server-url=http://example2.com'
            ],
            false,
            true
        );
        self::assertStringContainsString(
            '"servers":[{"url":"http://example1.com"},{"url":"http://example2.com"}]',
            $result
        );
    }
}
