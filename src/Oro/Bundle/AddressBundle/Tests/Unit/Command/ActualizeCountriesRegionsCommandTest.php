<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Command;

use Oro\Bundle\AddressBundle\Command\ActualizeCountriesRegionsCommand;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

class ActualizeCountriesRegionsCommandTest extends TestCase
{
    use TempDirExtension;

    private ActualizeCountriesRegionsCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->locator = $this->createMock(FileLocatorInterface::class);
        $this->externalFileFactory = $this->createMock(ExternalFileFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->kernel = $this->createMock(KernelInterface::class);

        $this->command = new ActualizeCountriesRegionsCommand(
            $this->locator,
            $this->externalFileFactory,
            $this->logger
        );
    }

    public function testShouldReturnErrorMessageIfOptionIsMissing(): void
    {
        $tester = new CommandTester($this->command);
        $tester->execute(['filepath' => 'var/data']);

        self::assertStringContainsString(
            'You must select one option: --update-full-data, --add-new-data or --remove-old-data',
            $tester->getDisplay()
        );
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testShouldPrepareDataAccordingToOption(
        string $option,
        array $expectedUpdatedTranslation,
        array $expectedUpdatedRegions
    ): void {
        $responseCountry = $this->createMock(ExternalFile::class);
        $responseRegion = $this->createMock(ExternalFile::class);

        $this->locator->expects($this->exactly(2))
            ->method('locate')
            ->willReturnOnConsecutiveCalls(
                __DIR__ . '/data/entities.en.yml',
                __DIR__ . '/data/countries.yml'
            );

        $this->externalFileFactory->expects($this->exactly(2))
            ->method('createFromUrl')
            ->willReturnOnConsecutiveCalls(
                $responseCountry,
                $responseRegion
            );

        $responseCountry->expects($this->once())
            ->method('getUrl')
            ->willReturn(__DIR__ . '/data/external_countries.json');

        $responseRegion->expects($this->once())
            ->method('getUrl')
            ->willReturn(__DIR__ . '/data/external_regions.json');

        $tmpDir = $this->getTempDir('download_dir');

        $tester = new CommandTester($this->command);
        $tester->execute([
            'filepath' => $tmpDir,
            $option => true
        ]);

        $generatedTranslationFile = Yaml::parse(
            file_get_contents(
                $tmpDir. DIRECTORY_SEPARATOR . 'entities.en.yml'
            )
        );

        $generatedCountriesFile = Yaml::parse(
            file_get_contents(
                $tmpDir. DIRECTORY_SEPARATOR . 'countries.yml'
            )
        );

        $this->assertEquals($expectedUpdatedTranslation, $generatedTranslationFile);
        $this->assertEquals($expectedUpdatedRegions, $generatedCountriesFile);
    }

    public function optionsDataProvider(): array
    {
        return [
            [
                '--update-full-data',
                $this->expectedUpdatedFullDataTranslationResult(),
                $this->expectedUpdatedFullDataCountriesRegionsResult()
            ],
            [
                '--add-new-data',
                $this->expectedAddNewDataTranslationResult(),
                $this->expectedAddNewDataCountriesRegionsResult()
            ],
             [
                 '--remove-old-data',
                 $this->expectedRemoveOldDataTranslationResult(),
                 $this->expectedRemoveOldDataCountriesRegionsResult()
             ],
        ];
    }

    private function expectedUpdatedFullDataTranslationResult(): array
    {
        return [
            'country' => [
                'ES' => 'Spain',
                'FI' => 'Finland',
                'FR' => 'France',
                'IT' => 'Italy',
                'XX' => 'Unnamed',
            ],
            'region' => [
                'ES-A' => 'Alacant*',
                'ES-AB' => 'Albacete',
                'ES-AL' => 'Almería',
                'FI-05' => 'Kainuu',
                'FI-08' => 'Keski-Suomi',
                'FR-01' => 'Ain',
                'FR-02' => 'Aisne',
                'FR-03' => 'Allier',
                'FR-04' => 'Alpes-de-Haute-Provence',
                'IT-21' => 'Piemonte',
                'IT-25' => 'Lombardia',
                'IT-999' => 'Some new Place',
            ]
        ];
    }

    private function expectedUpdatedFullDataCountriesRegionsResult(): array
    {
        return [
            'ES' => [
                'iso2Code' => 'ES',
                'iso3Code' => 'ESP',
                'regions' => [
                    'ES-A' => [
                        'combinedCode' => 'ES-A',
                        'code' => 'A'
                    ],
                    'ES-AB' => [
                        'combinedCode' => 'ES-AB',
                        'code' => 'AB'
                    ],
                    'ES-AL' => [
                        'combinedCode' => 'ES-AL',
                        'code' => 'AL'
                    ],
                ]
            ],
            'FI' => [
                'iso2Code' => 'FI',
                'iso3Code' => 'FIN',
                'regions' => [
                    'FI-05' => [
                        'combinedCode' => 'FI-05',
                        'code' => '05'
                    ],
                    'FI-08' => [
                        'combinedCode' => 'FI-08',
                        'code' => '08'
                    ],
                ]
            ],
            'FR' => [
                'iso2Code' => 'FR',
                'iso3Code' => 'FRA',
                'regions' => [
                    'FR-01' => [
                        'combinedCode' => 'FR-01',
                        'code' => '01'
                    ],
                    'FR-02' => [
                        'combinedCode' => 'FR-02',
                        'code' => '02'
                    ],
                    'FR-03' => [
                        'combinedCode' => 'FR-03',
                        'code' => '03'
                    ],
                    'FR-04' => [
                        'combinedCode' => 'FR-04',
                        'code' => '04'
                    ],
                ]
            ],
            'IT' => [
                'iso2Code' => 'IT',
                'iso3Code' => 'ITA',
                'regions' => [
                    'IT-21' => [
                        'combinedCode' => 'IT-21',
                        'code' => '21'
                    ],
                    'IT-25' => [
                        'combinedCode' => 'IT-25',
                        'code' => '25'
                    ],
                    'IT-999' => [
                        'combinedCode' => 'IT-999',
                        'code' => '999'
                    ],
                ]
            ],
            'XX' => [
                'iso2Code' => 'XX',
                'iso3Code' => 'XXX',
                'regions' => []
            ],
        ];
    }

    private function expectedAddNewDataTranslationResult(): array
    {
        return [
            'country' => [
                'ES' => 'Spain',
                'FI' => 'Finland',
                'FR' => 'France',
                'IT' => 'Italy',
                'QQ' => 'QQLand',
                'XX' => 'Unnamed',
            ],
            'region' => [
                'ES-A' => 'Alicante',
                'ES-AB' => 'Albacete',
                'ES-AL' => 'Barcelona',
                'FI-05' => 'Kainuu',
                'FI-08' => 'Keski-Suomi',
                'FR-01' => 'Ain',
                'FR-02' => 'Aisne',
                'FR-03' => 'Allier',
                'FR-04' => 'Alpes-de-Haute-Provence',
                'IT-21' => 'Piemonte',
                'IT-25' => 'Lombardia',
                'IT-999' => 'Some new Place',
            ]
        ];
    }

    private function expectedAddNewDataCountriesRegionsResult(): array
    {
        return [
            'ES' => [
                'iso2Code' => 'ES',
                'iso3Code' => 'ESP',
                'regions' => [
                    'ES-A' => [
                        'combinedCode' => 'ES-A',
                        'code' => 'A'
                    ],
                    'ES-AB' => [
                        'combinedCode' => 'ES-AB',
                        'code' => 'AB'
                    ],
                    'ES-AL' => [
                        'combinedCode' => 'ES-AL',
                        'code' => 'AL'
                    ],
                    'ES-TEST' => [
                        'combinedCode' => 'ES-TEST',
                        'code' => 'TEST'
                    ],
                ]
            ],
            'FI' => [
                'iso2Code' => 'FI',
                'iso3Code' => 'FIN',
                'regions' => [
                    'FI-05' => [
                        'combinedCode' => 'FI-05',
                        'code' => '05'
                    ],
                    'FI-08' => [
                        'combinedCode' => 'FI-08',
                        'code' => '08'
                    ],
                ]
            ],
            'FR' => [
                'iso2Code' => 'FR',
                'iso3Code' => 'FRA',
                'regions' => [
                    'FR-01' => [
                        'combinedCode' => 'FR-01',
                        'code' => '01'
                    ],
                    'FR-02' => [
                        'combinedCode' => 'FR-02',
                        'code' => '02'
                    ],
                    'FR-03' => [
                        'combinedCode' => 'FR-03',
                        'code' => '03'
                    ],
                    'FR-04' => [
                        'combinedCode' => 'FR-04',
                        'code' => '04'
                    ],
                ]
            ],
            'IT' => [
                'iso2Code' => 'IT',
                'iso3Code' => 'ITA',
                'regions' => [
                    'IT-21' => [
                        'combinedCode' => 'IT-21',
                        'code' => '21'
                    ],
                    'IT-25' => [
                        'combinedCode' => 'IT-25',
                        'code' => '25'
                    ],
                    'IT-999' => [
                        'combinedCode' => 'IT-999',
                        'code' => '999'
                    ],
                ]
            ],
            'XX' => [
                'iso2Code' => 'XX',
                'iso3Code' => 'XXX',
                'regions' => []
            ],
        ];
    }

    private function expectedRemoveOldDataTranslationResult(): array
    {
        return [
            'country' => [
                'ES' => 'Spain',
                'FR' => 'France',
                'IT' => 'Italy',
            ],
            'region' => [
                'ES-A' => 'Alicante',
                'ES-AB' => 'Albacete',
                'ES-AL' => 'Barcelona',
                'FR-01' => 'Ain',
                'FR-02' => 'Aisne',
                'FR-03' => 'Allier',
                'IT-21' => 'Piemonte',
                'IT-25' => 'Lombardia',
            ]
        ];
    }

    private function expectedRemoveOldDataCountriesRegionsResult(): array
    {
        return [
            'ES' => [
                'iso2Code' => 'ES',
                'iso3Code' => 'ESP',
                'regions' => [
                    'ES-A' => [
                        'combinedCode' => 'ES-A',
                        'code' => 'A'
                    ],
                    'ES-AB' => [
                        'combinedCode' => 'ES-AB',
                        'code' => 'AB'
                    ],
                    'ES-AL' => [
                        'combinedCode' => 'ES-AL',
                        'code' => 'AL'
                    ],
                ]
            ],
            'IT' => [
                'iso2Code' => 'IT',
                'iso3Code' => 'ITA',
                'regions' => [
                    'IT-21' => [
                        'combinedCode' => 'IT-21',
                        'code' => '21'
                    ],
                    'IT-25' => [
                        'combinedCode' => 'IT-25',
                        'code' => '25'
                    ],
                ]
            ],
            'FR' => [
                'iso2Code' => 'FR',
                'iso3Code' => 'FRA',
                'regions' => [
                    'FR-01' => [
                        'combinedCode' => 'FR-01',
                        'code' => '01'
                    ],
                    'FR-02' => [
                        'combinedCode' => 'FR-02',
                        'code' => '02'
                    ],
                    'FR-03' => [
                        'combinedCode' => 'FR-03',
                        'code' => '03'
                    ],
                ]
            ],
        ];
    }
}
