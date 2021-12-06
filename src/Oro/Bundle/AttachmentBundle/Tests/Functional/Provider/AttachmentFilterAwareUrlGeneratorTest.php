<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Provider;

use Oro\Bundle\AttachmentBundle\Configurator\AttachmentFilterConfiguration;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentFilterAwareUrlGenerator;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Configurator\AttachmentSettingsTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class AttachmentFilterAwareUrlGeneratorTest extends WebTestCase
{
    use AttachmentSettingsTrait;

    private const FILTER_NAME = 'avatar_med';

    /** @var AttachmentFilterAwareUrlGenerator */
    private $attachmentUrlGenerator;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->attachmentUrlGenerator =
            $this->getContainer()->get('oro_attachment.url_generator');
    }

    public function testWithLibrariesExistsAndProcessorsNotAllowed(): void
    {
        // Libraries exists and processors not allowed but quality parameters was changed
        // Always accept the configuration(post_processor => [...]) for the filter if libraries exist
        // and the configuration has been changed!
        $this->changeProcessorsParameters(1, 1, false);
        $filterParameters = array_merge($this->getAvatarMedConfig(), ['post_processors' => []]);
        $url = $this->attachmentUrlGenerator->generate('oro_filtered_attachment', [
            'id' => '1',
            'filter' => self::FILTER_NAME,
            'filename' => 'filename'
        ]);

        $this->assertStringContainsString($this->getHash($filterParameters), $url);
    }

    public function testWithLibrariesExistsAndProcessorsAllowedAndConfigNotChanged(): void
    {
        // Libraries exists and processors allowed but quality parameters was not changed
        // Always accept the configuration(post_processor => [...]) for the filter if libraries exist
        // and the configuration has been changed!
        $this->changeProcessorsParameters();
        $filterParameters = array_merge($this->getAvatarMedConfig(), ['post_processors' => []]);
        $url = $this->attachmentUrlGenerator->generate('oro_filtered_attachment', [
            'id' => '1',
            'filter' => self::FILTER_NAME,
            'filename' => 'filename'
        ]);

        $this->assertStringContainsString($this->getHash($filterParameters), $url);
    }

    public function testWithLibrariesExistsAndProcessorsAllowedAndConfigChanged(): void
    {
        $this->changeProcessorsParameters(50, 50);
        $filterParameters = array_merge(
            $this->getAvatarMedConfig(),
            [
                'post_processors' => [
                    'pngquant' => ['quality' => 50],
                    'jpegoptim' => ['strip_all' => true, 'max' => 50, 'progressive' => false],
                ]
            ]
        );
        $url = $this->attachmentUrlGenerator->generate('oro_filtered_attachment', [
            'id' => '1',
            'filter' => self::FILTER_NAME,
            'filename' => 'filename'
        ]);

        $this->assertStringContainsString($this->getHash($filterParameters), $url);
    }

    private function getAvatarMedConfig(): array
    {
        /** @var AttachmentFilterConfiguration $attachmentFilterConfiguration */
        $attachmentFilterConfiguration =
            $this->getContainer()->get('oro_attachment.configurator.attachment_filter_configuration');

        return $attachmentFilterConfiguration->getOriginal(self::FILTER_NAME);
    }

    private function getHash(array $parameters): string
    {
        return md5(json_encode($parameters, JSON_THROW_ON_ERROR));
    }
}
