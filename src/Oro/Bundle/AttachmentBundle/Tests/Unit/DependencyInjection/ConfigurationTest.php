<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private function processConfiguration(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $config);
    }

    public function testProcessConfigurationWhenDefault(): void
    {
        $config = $this->processConfiguration([]);
        unset($config['settings']);

        self::assertEquals(
            [
                'debug_images' => true,
                'maxsize' => 10,
                'upload_file_mime_types' => [],
                'upload_image_mime_types' => [],
                'inline_mime_types' => [
                    'image/gif',
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'application/pdf',
                    'text/plain',
                    'text/csv',
                ],
                'external_file_details_http_methods' => [],
                'png_quality' => 100,
                'jpeg_quality' => 85,
                'processors_allowed' => true,
                'webp_strategy' => WebpConfiguration::ENABLED_IF_SUPPORTED,
                'cleanup' => [
                    'collect_attachment_files_batch_size' => 20000,
                    'load_existing_attachments_batch_size' => 500,
                    'load_attachments_batch_size' => 10000
                ],
                'metadata_service' => [
                    'service_url' => '%env(default:oro_attachment.metadata_service_url_default:'
                        . 'string:ORO_METADATA_SERVICE_URL)%',
                    'api_key' => '%env(default:oro_attachment.metadata_service_api_key_default:'
                        . 'trim:string:ORO_METADATA_SERVICE_API_KEY)%'
                ]
            ],
            $config
        );
    }

    /**
     * @dataProvider webpStrategyDataProvider
     */
    public function testProcessConfigurationWhenWebpStrategy(string $webpStrategy): void
    {
        $config = $this->processConfiguration(['oro_attachment' => ['webp_strategy' => $webpStrategy]]);

        self::assertEquals($webpStrategy, $config['webp_strategy']);
    }

    public function webpStrategyDataProvider(): array
    {
        return [
            [WebpConfiguration::ENABLED_FOR_ALL],
            [WebpConfiguration::ENABLED_IF_SUPPORTED],
            [WebpConfiguration::DISABLED],
        ];
    }

    public function testProcessConfigurationWhenInvalidWebpStrategy(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectErrorMessage('The value "invalid" is not allowed for path "oro_attachment.webp_strategy".');

        $this->processConfiguration(['oro_attachment' => ['webp_strategy' => 'invalid']]);
    }

    public function testProcessConfigurationWhenInlineMimeTypes(): void
    {
        $config = $this->processConfiguration(
            ['oro_attachment' => ['inline_mime_types' => ['Image/PNG; q=1', 'video/mp4', 'image/png']]]
        );

        self::assertSame(['image/png', 'video/mp4'], $config['inline_mime_types']);
    }

    public function testProcessConfigurationWhenInlineMimeTypesFromSeveralSources(): void
    {
        $config = $this->processConfiguration([
            ['inline_mime_types' => ['image/png', 'application/pdf']],
            ['inline_mime_types' => ['video/mp4', 'image/png']]
        ]);

        self::assertSame(['image/png', 'application/pdf', 'video/mp4'], $config['inline_mime_types']);
    }

    public function testProcessConfigurationWhenEmptyInlineMimeTypes(): void
    {
        $config = $this->processConfiguration(['oro_attachment' => ['inline_mime_types' => []]]);

        self::assertSame([], $config['inline_mime_types']);
    }

    /**
     * @dataProvider notAllowedInlineMimeTypeDataProvider
     */
    public function testProcessConfigurationWhenNotAllowedInlineMimeType(string $mimeType): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('can run scripts');

        $this->processConfiguration(['oro_attachment' => ['inline_mime_types' => ['image/png', $mimeType]]]);
    }

    public function notAllowedInlineMimeTypeDataProvider(): array
    {
        return [
            'html' => ['text/html'],
            'html in upper case with a parameter' => ['TEXT/HTML; charset=utf-8'],
            'svg' => ['image/svg+xml'],
            'xhtml' => ['application/xhtml+xml'],
            'text xml' => ['text/xml'],
            'application xml' => ['application/xml'],
            'text javascript' => ['text/javascript'],
            'ecmascript' => ['application/ecmascript'],
            'xml based type with several plus signs' => ['application/vnd.example+v2+xml'],
            'x-javascript' => ['application/x-javascript'],
            'jscript' => ['text/jscript'],
            'livescript' => ['text/livescript'],
            'vbscript' => ['text/vbscript'],
            'xsl' => ['text/xsl'],
            'cache manifest' => ['text/cache-manifest'],
            'html component' => ['text/x-component'],
            'htc' => ['text/htc'],
            'scriptlet' => ['text/x-scriptlet'],
            'shockwave flash' => ['application/x-shockwave-flash'],
            'hta' => ['application/hta'],
        ];
    }

    /**
     * @dataProvider invalidInlineMimeTypeDataProvider
     */
    public function testProcessConfigurationWhenInvalidInlineMimeType(string $mimeType): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('is not a valid MIME type');

        $this->processConfiguration(['oro_attachment' => ['inline_mime_types' => [$mimeType]]]);
    }

    public function invalidInlineMimeTypeDataProvider(): array
    {
        return [
            'without subtype' => ['image'],
        ];
    }
}
