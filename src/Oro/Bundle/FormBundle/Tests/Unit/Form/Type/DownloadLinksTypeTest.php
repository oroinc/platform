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

    protected function setUp()
    {
        $this->assetHelper = $this->getMockBuilder('Symfony\Component\Templating\Helper\CoreAssetsHelper')
            ->disableOriginalConstructor()->getMock();
        $this->type        = new DownloadLinksType($this->assetHelper);
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
            file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName, '');
            if (isset($expected['files'][$fileName])) {
                array_push(
                    $valueMap,
                    [
                        $options['source']['url'] . DIRECTORY_SEPARATOR . $fileName,
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

        foreach ($files as $fileName) {
            unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName);
        }
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
                        'path' => sys_get_temp_dir() . '/*.download_file',
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
                        'path' => sys_get_temp_dir() . '/*.download_file',
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
