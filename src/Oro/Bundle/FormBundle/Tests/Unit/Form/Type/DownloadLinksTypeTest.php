<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;

use Oro\Bundle\FormBundle\Form\Type\DownloadLinksType;

class DownloadLinksTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var DownloadLinksType */
    protected $type;

    /** @var CoreAssetsHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $assetHelper;

    /** @var  string */
    protected $testDir;

    protected function setUp()
    {
        $this->assetHelper = $this->getMockBuilder('Symfony\Component\Templating\Helper\CoreAssetsHelper')
            ->disableOriginalConstructor()->getMock();
        $this->type        = new DownloadLinksType($this->assetHelper);
        $this->testDir     = $this->getTestDir();
        $this->removeTestDir();
        mkdir($this->testDir);
    }

    protected function tearDown()
    {
        $this->removeTestDir();
        unset($this->testDir, $this->type, $this->assetHelper);
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->type->getName());
        $this->assertEquals('oro_download_links_type', $this->type->getName());
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "source" is missing.
     */
    public function testSetDefaultOptionsWithoutSource()
    {
        $resolver = new OptionsResolver();
        $this->type->setDefaultOptions($resolver);
        $resolver->resolve([]);
    }

    public function testSetDefaultOptions()
    {
        $resolver = new OptionsResolver();
        $this->type->setDefaultOptions($resolver);

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
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $view = new FormView();

        $valueMap = [];
        foreach ($files as $fileName) {
            file_put_contents($this->testDir . DIRECTORY_SEPARATOR . $fileName, '');
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
        return [
            'no files'       => [
                'files'    => [],
                'options'  => [
                    'source' => [
                        'path' =>  $this->getTestDir() . '/*.download_file',
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
                        'path' => $this->getTestDir() . '/*.download_file',
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

    /**
     * Get test dir path
     *
     * @return string
     */
    protected function getTestDir()
    {
        $tmpDir = ini_get('upload_tmp_dir');
        if (!$tmpDir || !is_dir($tmpDir) || !is_writable($tmpDir)) {
            $tmpDir = sys_get_temp_dir();
        }

        return $tmpDir . DIRECTORY_SEPARATOR . 'oro_download_dir';
    }

    /**
     * Remove test dir
     */
    protected function removeTestDir()
    {
        if (is_dir($this->testDir)) {
            $files = new \RecursiveDirectoryIterator($this->testDir, \RecursiveDirectoryIterator::SKIP_DOTS);
            foreach ($files as $fileInfo) {
                unlink($fileInfo->getRealPath());
            }

            rmdir($this->testDir);
        }
    }
}
