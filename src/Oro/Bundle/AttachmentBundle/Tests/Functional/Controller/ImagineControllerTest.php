<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFileSystem;

class ImagineControllerTest extends WebTestCase
{
    private string $imagePath;

    private string $publicDir;

    protected function setUp(): void
    {
        $this->initClient();

        $this->imagePath = 'media/images/' . uniqid('image_', true) . '.jpg';
        $this->publicDir = $this->client->getKernel()->getProjectDir() . '/public/';

        $filesystem = new SymfonyFileSystem();
        $filesystem->copy(
            __DIR__ . '/../DataFixtures/files/image.jpg',
            $this->publicDir . $this->imagePath,
            true
        );
    }

    protected function tearDown(): void
    {
        $this->client->enableReboot();

        $filesystem = new SymfonyFileSystem();
        $filesystem->remove($this->publicDir . $this->imagePath);
    }

    public function testGetFilteredImageReturns404WhenFileNotExists(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_imagine_filter',
                [
                    'path' => 'missing/image.png',
                    'filter' => 'original',
                ]
            )
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testGetFilteredImageReturns404WhenFilterNotExists(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_imagine_filter',
                [
                    'path' => $this->imagePath,
                    'filter' => 'invalid_filter',
                ]
            )
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testGetFilteredImage(): void
    {
        $expectedImageCachePath = 'media/cache/original/' . $this->imagePath;

        $url = self::getContainer()->get('oro_attachment.tests.imagine.provider.url')
            ->getFilteredImageUrl($this->imagePath, 'original');
        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 302);
        self::assertEquals($expectedImageCachePath, ltrim(parse_url($result->getTargetUrl(), PHP_URL_PATH), '/'));
    }

    public function testGetFilteredImageReturnsWebpImageWhenFormatIsWebp(): void
    {
        $expectedImageCachePath = 'media/cache/original/' . $this->imagePath . '.webp';

        $url = self::getContainer()->get('oro_attachment.tests.imagine.provider.url')
            ->getFilteredImageUrl($this->imagePath, 'original', 'webp');
        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 302);
        self::assertEquals($expectedImageCachePath, ltrim(parse_url($result->getTargetUrl(), PHP_URL_PATH), '/'));
    }

    public function testGetFilteredImageReturnsPngImageWhenFilterConfigHasFormatPng(): void
    {
        // Prevents kernel from shutting down before request is made to keep the added LiipImagine filter.
        $this->client->disableReboot();

        $filterName = md5(uniqid(__METHOD__, true));
        self::getContainer()->get('liip_imagine.filter.configuration')
            ->set($filterName, ['format' => 'png']);

        $expectedImageCachePath = 'media/cache/' . $filterName . '/' . $this->imagePath . '.png';

        $url = self::getContainer()->get('oro_attachment.tests.imagine.provider.url')
            ->getFilteredImageUrl($this->imagePath, $filterName);
        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 302);
        self::assertEquals($expectedImageCachePath, ltrim(parse_url($result->getTargetUrl(), PHP_URL_PATH), '/'));
    }

    public function testGetFilteredImageReturnsWebpImageWhenFilterConfigHasFormatPng(): void
    {
        // Prevents kernel from shutting down before request is made to keep the added LiipImagine filter.
        $this->client->disableReboot();

        $filterName = md5(uniqid(__METHOD__, true));
        self::getContainer()->get('liip_imagine.filter.configuration')
            ->set($filterName, ['format' => 'png']);

        $expectedImageCachePath = 'media/cache/' . $filterName . '/' . $this->imagePath . '.webp';

        $url = self::getContainer()->get('oro_attachment.tests.imagine.provider.url')
            ->getFilteredImageUrl($this->imagePath, $filterName, 'webp');
        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 302);
        self::assertEquals($expectedImageCachePath, ltrim(parse_url($result->getTargetUrl(), PHP_URL_PATH), '/'));
    }
}
