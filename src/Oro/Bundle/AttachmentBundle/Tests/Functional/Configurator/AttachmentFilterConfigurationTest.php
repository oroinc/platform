<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Configurator;

use Oro\Bundle\AttachmentBundle\Configurator\AttachmentFilterConfiguration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class AttachmentFilterConfigurationTest extends WebTestCase
{
    use AttachmentSettingsTrait;

    /** @var AttachmentFilterConfiguration */
    private $attachmentFilterConfigurator;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->attachmentFilterConfigurator =
            $this->getContainer()->get('oro_attachment.configurator.attachment_filter_configuration');
    }

    public function testWithLibrariesExistsAndProcessorsNotAllowed(): void
    {
        $this->changeProcessorsParameters(1, 1, false);
        $filters = $this->attachmentFilterConfigurator->all();
        $this->assertNotEmpty($filters);
        foreach ($filters as $filterConfig) {
            $this->assertEmpty($filterConfig['post_processors']);
        }
    }

    public function testWithLibrariesExistsAndProcessorsAllowedAndConfigNotChanged(): void
    {
        $this->changeProcessorsParameters();
        $filters = $this->attachmentFilterConfigurator->all();
        foreach ($filters as $filterName => $filterConfig) {
            $specificFilterConfig = $this->attachmentFilterConfigurator->get($filterName);
            $this->assertFilterEqual($filterConfig, 85, 100);
            $this->assertFilterEqual($specificFilterConfig, 85, 100);
        }
    }

    public function testWithLibrariesExistsAndProcessorsAllowedAndConfigChanged(): void
    {
        $this->changeProcessorsParameters(65, 35);
        $filters = $this->attachmentFilterConfigurator->all();
        foreach ($filters as $filterName => $filterConfig) {
            $specificFilterConfig = $this->attachmentFilterConfigurator->get($filterName);
            $this->assertFilterEqual($filterConfig, 65, 35);
            $this->assertFilterEqual($specificFilterConfig, 65, 35);
        }
    }

    private function assertFilterEqual(array $filter, int $jpegQuality = 85, int $pngQuality = 100): void
    {
        $this->assertEquals(
            [
                'pngquant' => ['quality' => $pngQuality],
                'jpegoptim' => ['strip_all' => true, 'max' => $jpegQuality, 'progressive' => false],
            ],
            $filter['post_processors']
        );
    }
}
