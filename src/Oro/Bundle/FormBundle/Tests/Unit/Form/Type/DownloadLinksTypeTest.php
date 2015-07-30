<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\DownloadLinksType;

class DownloadLinksTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var DownloadLinksType */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
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
        $testDir = $this->getTestDir();
        $this->removeTestDir($testDir);
        mkdir($testDir);

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
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

        $this->removeTestDir($testDir);
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
                        'path' => $this->getTestDir() . '/*.download_file',
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
        $tmpDir = sys_get_temp_dir();
        if (!($tmpDir && is_dir($tmpDir) && is_writable($tmpDir))) {
            $this->markTestSkipped(sprintf('This test requires access on create dir in temp folder "%s"', $tmpDir));
        }

        return $tmpDir . DIRECTORY_SEPARATOR . 'oro_download_dir';
    }

    /**
     * Remove test dir
     *
     * @param string $dir
     */
    protected function removeTestDir($dir)
    {
        if (is_dir($dir)) {
            $files = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
            foreach ($files as $fileInfo) {
                unlink($fileInfo->getRealPath());
            }

            rmdir($dir);
        }
    }
}
