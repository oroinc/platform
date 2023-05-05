<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
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
                'png_quality' => 100,
                'jpeg_quality' => 85,
                'processors_allowed' => true,
                'webp_strategy' => WebpConfiguration::ENABLED_IF_SUPPORTED,
                'cleanup' => [
                    'collect_attachment_files_batch_size' => 20000,
                    'load_existing_attachments_batch_size' => 500,
                    'load_attachments_batch_size' => 10000
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
}
