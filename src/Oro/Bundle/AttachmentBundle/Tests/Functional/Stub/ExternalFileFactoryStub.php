<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Stub;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;

/**
 * The decorator for ExternalFileFactory that allows to substitute
 * an external url processing in functional tests.
 */
class ExternalFileFactoryStub extends ExternalFileFactory
{
    public const IMAGE_A_TEST_URL = 'http://example.org/public/image-a.png';
    public const IMAGE_B_TEST_URL = 'http://example.org/public/image-b.png';
    public const FILE_A_TEST_URL = 'http://example.org/public/file-a.txt';

    private const MOCKED_FILES = [
        self::IMAGE_A_TEST_URL => [
            'originalName' => 'image-a.png',
            'size' => 95,
            'mimeType' => 'image/png',
        ],
        self::IMAGE_B_TEST_URL => [
            'originalName' => 'image-b.png',
            'size' => 96,
            'mimeType' => 'image/png',
        ],
        self::FILE_A_TEST_URL => [
            'originalName' => 'file-a.txt',
            'size' => 95,
            'mimeType' => 'text/plain',
        ],
    ];

    private ExternalFileFactory $innerFactory;

    public function __construct(ExternalFileFactory $innerFactory)
    {
        $this->innerFactory = $innerFactory;
    }

    public function createFromUrl(string $url): ExternalFile
    {
        if (\array_key_exists($url, self::MOCKED_FILES)) {
            return new ExternalFile(
                $url,
                self::MOCKED_FILES[$url]['originalName'],
                self::MOCKED_FILES[$url]['size'],
                self::MOCKED_FILES[$url]['mimeType']
            );
        }

        return $this->innerFactory->createFromUrl($url);
    }
}
