<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\DownloadLinksType;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DownloadLinksTypeTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var DownloadLinksType */
    protected $type;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $assetHelper;

    protected function setUp()
    {
        $this->assetHelper = $this->getMockBuilder('Symfony\Component\Asset\Packages')
            ->disableOriginalConstructor()
            ->getMock();
        $this->type        = new DownloadLinksType($this->assetHelper);
    }

    protected function tearDown()
    {
        unset($this->type, $this->assetHelper);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "source" is missing.
     */
    public function testConfigureOptionsWithoutSource()
    {
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
     * @param array $files
     * @param array $options
     * @param array $expected
     *
     * @dataProvider optionsProvider
     */
    public function testFinishView(array $files, array $options, array $expected)
    {
        $testDir = $this->getTempDir('download_dir');

        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $view = new FormView();

        $valueMap = [];
        foreach ($files as $fileName) {
            file_put_contents($testDir . DIRECTORY_SEPARATOR . $fileName, '');
            if (isset($expected['files'][$fileName])) {
                array_push(
                    $valueMap,
                    [
                        $options['source']['url'] . '/' . $fileName,
                        null,
                        $expected['files'][$fileName]
                    ]
                );
            }
        }
        $this->assetHelper->expects($this->exactly(count($files)))->method('getUrl')
            ->willReturnMap($valueMap);

        $this->type->finishView($view, $form, $options);
        $this->assertEquals($expected, $view->vars);
    }

    /**
     * @return array
     */
    public function optionsProvider()
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
