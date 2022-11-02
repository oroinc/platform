<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Guesser;

use Oro\Bundle\AttachmentBundle\Guesser\MsMimeTypeGuesser;

class MsMimeTypeGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var MsMimeTypeGuesser */
    private $guesser;

    private array $files = [];

    private static array $fileDefaults = [
        'name'     => null,
        'tmp_name' => null,
        'error'    => 0,
        'size'     => 0,
        'type'     => ''
    ];

    protected function setUp(): void
    {
        $this->guesser = new MsMimeTypeGuesser();
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            unlink($file);
        }
    }

    public function testIsGuesserSupported(): void
    {
        $this->assertTrue($this->guesser->isGuesserSupported());
    }

    /**
     * @dataProvider guessDataProvider
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testGuessMimeType(string $path, array $files, ?string $expectedMimeType): void
    {
        $GLOBALS['_FILES'] = $files;
        $this->assertEquals($expectedMimeType, $this->guesser->guessMimeType($path));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function guessDataProvider(): array
    {
        $correctFile = realpath(__DIR__ . '/../Fixtures/testFile/test.msg');
        $incorrectFile = realpath(__DIR__ . '/../Fixtures/testFile/invalid.msg');
        $textFile = realpath(__DIR__ . '/../Fixtures/testFile/test.txt');

        return [
            'msg file not uploaded'           => [
                'path'             => $correctFile,
                'files'            => $this->buildFilesArraySimple([]),
                'expectedMimeType' => 'application/vnd.ms-outlook'
            ],
            'invalid msg file not uploaded'   => [
                'path'             => $incorrectFile,
                'files'            => $this->buildFilesArraySimple([]),
                'expectedMimeType' => null
            ],
            'txt file not uploaded'           => [
                'path'             => $textFile,
                'files'            => $this->buildFilesArraySimple([]),
                'expectedMimeType' => null
            ],
            'msg file simple form'            => [
                'path'             => $correctFile,
                'files'            => $this->buildFilesArraySimple(
                    [
                        ['tmp_name' => $textFile],
                        ['tmp_name' => $correctFile],
                        ['tmp_name' => $incorrectFile]
                    ]
                ),
                'expectedMimeType' => 'application/vnd.ms-outlook'
            ],
            'invalid msg file simple form'    => [
                'path'             => $incorrectFile,
                'files'            => $this->buildFilesArraySimple(
                    [
                        ['tmp_name' => $textFile],
                        ['tmp_name' => $correctFile],
                        ['tmp_name' => $incorrectFile]
                    ]
                ),
                'expectedMimeType' => null
            ],
            'txt file simple form'            => [
                'path'             => $textFile,
                'files'            => $this->buildFilesArraySimple(
                    [
                        ['tmp_name' => $textFile]
                    ]
                ),
                'expectedMimeType' => null
            ],
            'bad extension simple form'       => [
                'path'             => $correctFile,
                'files'            => $this->buildFilesArraySimple(
                    [
                        ['name' => 'text.txt', 'tmp_name' => $correctFile]
                    ]
                ),
                'expectedMimeType' => null
            ],
            'msg file complex form (level 1)' => [
                'path'             => $correctFile,
                'files'            => $this->buildFilesArrayComplex(
                    [
                        ['tmp_name' => $textFile],
                        ['tmp_name' => $correctFile]
                    ]
                ),
                'expectedMimeType' => 'application/vnd.ms-outlook'
            ],
            'msg file complex form (level 2)' => [
                'path'             => $correctFile,
                'files'            => $this->buildFilesArrayComplex(
                    [
                        ['tmp_name' => $textFile],
                        ['tmp_name' => $correctFile]
                    ],
                    2
                ),
                'expectedMimeType' => 'application/vnd.ms-outlook'
            ],
            'msg file complex form (level 3)' => [
                'path'             => $correctFile,
                'files'            => $this->buildFilesArrayComplex(
                    [
                        ['tmp_name' => $textFile],
                        ['tmp_name' => $correctFile]
                    ],
                    3
                ),
                'expectedMimeType' => 'application/vnd.ms-outlook'
            ],
            'txt file complex form'           => [
                'path'             => $textFile,
                'files'            => $this->buildFilesArrayComplex(
                    [
                        ['tmp_name' => $textFile],
                    ]
                ),
                'expectedMimeType' => null
            ],
            'bad extension complex form'      => [
                'path'             => $correctFile,
                'files'            => $this->buildFilesArrayComplex(
                    [
                        ['name' => 'text.txt', 'tmp_name' => $correctFile],
                    ]
                ),
                'expectedMimeType' => null
            ]
        ];
    }

    private function buildFilesArraySimple(array $files): array
    {
        $result = [];
        foreach ($files as $index => $file) {
            $key = 'file_' . $index;
            if (!array_key_exists('name', $file)) {
                $file['name'] = pathinfo($file['tmp_name'], PATHINFO_BASENAME);
            }
            $result[$key] = array_merge(self::$fileDefaults, $file);
        }

        return $result;
    }

    private function buildFilesArrayComplex(array $files, int $level = 1): array
    {
        $result = [];
        foreach (self::$fileDefaults as $fileKey => $defaultValue) {
            $data = [];
            foreach ($files as $index => $file) {
                $key = 'file_' . $index;
                if (!array_key_exists('name', $file)) {
                    $file['name'] = pathinfo($file['tmp_name'], PATHINFO_BASENAME);
                }
                $data[$key] = $file[$fileKey] ?? $defaultValue;
            }

            for ($i = $level; $i > 0; --$i) {
                $data = array_merge([$data]);
            }

            $result['form_name'][$fileKey] = $data;
        }

        return $result;
    }
}
