<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FileUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var FileUrlProvider */
    private $provider;

    protected function setUp()
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->provider = new FileUrlProvider($this->urlGenerator);
    }

    public function testGetFileUrl(): void
    {
        $this->urlGenerator
            ->method('generate')
            ->with(
                'oro_attachment_get_file',
                [
                    'id' => $fileId = 1,
                    'filename' => $filename = 'sample-filename',
                    'action' => $action = 'sample-action',
                ],
                $referenceType = 1
            )
            ->willReturn($url = 'sample-url');

        self::assertEquals(
            $url,
            $this->provider->getFileUrl($this->getFile($fileId, $filename), $action, $referenceType)
        );
    }

    public function testGetResizedImageUrl(): void
    {
        $this->urlGenerator
            ->method('generate')
            ->with(
                'oro_resize_attachment',
                [
                    'id' => $fileId = 1,
                    'filename' => $filename = 'sample-filename',
                    'width' => $width = 10,
                    'height' => $height = 20,
                ],
                $referenceType = 1
            )
            ->willReturn($url = 'sample-url');

        self::assertEquals(
            $url,
            $this->provider->getResizedImageUrl($this->getFile($fileId, $filename), $width, $height, $referenceType)
        );
    }

    public function testGetFilteredImageUrl(): void
    {
        $this->urlGenerator
            ->method('generate')
            ->with(
                'oro_filtered_attachment',
                [
                    'id' => $fileId = 1,
                    'filename' => $filename = 'sample-filename',
                    'filter' => $filter = 'sample-filter',
                ],
                $referenceType = 1
            )
            ->willReturn($url = 'sample-url');

        self::assertEquals(
            $url,
            $this->provider->getFilteredImageUrl($this->getFile($fileId, $filename), $filter, $referenceType)
        );
    }

    /**
     * @param int|null $id
     * @param string $filename
     *
     * @return File
     */
    private function getFile(int $id = null, string $filename = ''): File
    {
        $file = $this->createMock(File::class);
        $file
            ->method('getId')
            ->willReturn($id);

        $file
            ->method('getFilename')
            ->willReturn($filename);

        return $file;
    }
}
