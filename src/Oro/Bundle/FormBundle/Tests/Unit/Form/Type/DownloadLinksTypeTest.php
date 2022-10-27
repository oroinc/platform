<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\DownloadLinksType;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DownloadLinksTypeTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var Packages|\PHPUnit\Framework\MockObject\MockObject */
    private $assetHelper;

    /** @var DownloadLinksType */
    private $type;

    protected function setUp(): void
    {
        $this->assetHelper = $this->createMock(Packages::class);

        $this->type = new DownloadLinksType($this->assetHelper);
    }

    public function testConfigureOptionsWithoutSource()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "source" is missing.');

        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);
        $resolver->resolve([]);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        $options         = ['source' => []];
        $resolvedOptions = $resolver->resolve($options);
        $this->assertEquals(
            [
                'source' => [],
                'class'  => ''
            ],
            $resolvedOptions
        );
    }

    /**
     * @dataProvider optionsProvider
     */
    public function testFinishView(array $files, array $options, array $expected)
    {
        $testDir = $this->getTempDir('download_dir');

        $form = $this->createMock(FormInterface::class);
        $view = new FormView();

        $valueMap = [];
        foreach ($files as $fileName) {
            file_put_contents($testDir . DIRECTORY_SEPARATOR . $fileName, '');
            if (isset($expected['files'][$fileName])) {
                $valueMap[] = [
                    $options['source']['url'] . '/' . $fileName,
                    null,
                    $expected['files'][$fileName]
                ];
            }
        }
        $this->assetHelper->expects($this->exactly(count($files)))
            ->method('getUrl')
            ->willReturnMap($valueMap);

        $this->type->finishView($view, $form, $options);
        $this->assertEquals($expected, $view->vars);
    }

    public function optionsProvider(): array
    {
        $downloadDir = $this->getTempDir('download_dir', null);

        return [
            'no files'       => [
                'files'    => [],
                'options'  => [
                    'source' => [
                        'path' => $downloadDir . '/*.download_file',
                        'url'  => 'download/files'
                    ],
                    'class'  => ''
                ],
                'expected' => [
                    'value' => null,
                    'attr'  => [],
                    'files' => [],
                    'class' => ''
                ]
            ],
            'existing files' => [
                'files'    => [
                    'file1.download_file',
                    'file2.download_file',
                ],
                'options'  => [
                    'source' => [
                        'path' => $downloadDir . '/*.download_file',
                        'url'  => 'download/files'
                    ],
                    'class'  => 'red'
                ],
                'expected' => [
                    'value' => null,
                    'attr'  => [],
                    'files' => [
                        'file1.download_file' => '/download/files/file1.download_file',
                        'file2.download_file' => '/download/files/file2.download_file'
                    ],
                    'class' => 'red'
                ]
            ]
        ];
    }
}
