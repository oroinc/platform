<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Entity;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DigitalAssetBundle\Provider\MimeTypesProvider;

class MimeTypesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var MimeTypesProvider */
    private $provider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->provider = new MimeTypesProvider($this->configManager);
    }

    /**
     * @dataProvider mimeTypesDataProvider
     *
     * @param string|null $fileMimeTypes
     * @param string|null $imageMimeTypes
     * @param array $expected
     */
    public function testGetMimeTypes(?string $fileMimeTypes, ?string $imageMimeTypes, array $expected): void
    {
        $this->configManager
            ->method('get')
            ->willReturnMap([
                ['oro_attachment.upload_file_mime_types', '', false, null, $fileMimeTypes],
                ['oro_attachment.upload_image_mime_types', '', false, null, $imageMimeTypes],
            ]);

        $this->assertEquals($expected, $this->provider->getMimeTypes());
    }

    /**
     * @return array
     */
    public function mimeTypesDataProvider(): array
    {
        return [
            [null, null, []],
            [
                'sample/type1',
                'sample/type2',
                ['sample/type1', 'sample/type2'],
            ],
            [
                'sample/type1',
                '',
                ['sample/type1'],
            ],
            [
                '',
                'sample/type2',
                ['sample/type2'],
            ],
            [
                'sample/type1,sample/type2',
                'sample/type2',
                ['sample/type1', 'sample/type2'],
            ],
        ];
    }

    /**
     * @dataProvider mimeTypesAsChoicesDataProvider
     *
     * @param string|null $fileMimeTypes
     * @param string|null $imageMimeTypes
     * @param array $expected
     */
    public function testGetMimeTypesAsChoices(?string $fileMimeTypes, ?string $imageMimeTypes, array $expected): void
    {
        $this->configManager
            ->method('get')
            ->willReturnMap([
                ['oro_attachment.upload_file_mime_types', '', false, null, $fileMimeTypes],
                ['oro_attachment.upload_image_mime_types', '', false, null, $imageMimeTypes],
            ]);

        $this->assertEquals($expected, $this->provider->getMimeTypesAsChoices());
    }

    /**
     * @return array
     */
    public function mimeTypesAsChoicesDataProvider(): array
    {
        return [
            [null, null, []],
            [
                'sample/type1',
                'sample/type2',
                ['sample/type1' => 'sample/type1', 'sample/type2' => 'sample/type2'],
            ],
            [
                'sample/type1',
                '',
                ['sample/type1' => 'sample/type1'],
            ],
            [
                '',
                'sample/type2',
                ['sample/type2' => 'sample/type2'],
            ],
            [
                'sample/type1,sample/type2',
                'sample/type2',
                ['sample/type1' => 'sample/type1', 'sample/type2' => 'sample/type2'],
            ],
        ];
    }
}
