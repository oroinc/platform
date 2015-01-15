<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Templating\Helper\CoreAssetsHelper;

use Oro\Bundle\FormBundle\Form\Type\DownloadLinksType;

class DownloadLinksTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DownloadLinksType
     */
    protected $type;

    /**
     * @var CoreAssetsHelper
     */
    protected $assetHelper;

    protected function setUp()
    {
        $this->assetHelper = $this->getMockBuilder('Symfony\Component\Templating\Helper\CoreAssetsHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new DownloadLinksType($this->assetHelper);
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->type->getName());
        $this->assertEquals('oro_download_links_type', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertInternalType('string', $this->type->getParent());
        $this->assertEquals('text', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');

        $resolver
            ->expects($this->once())
            ->method('setRequired')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $resolver
            ->expects($this->once())
            ->method('setOptional')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $resolver
            ->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $resolver
            ->expects($this->once())
            ->method('setAllowedTypes')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $this->type->setDefaultOptions($resolver);
    }

    /**
     * @param array $files
     * @param array $options
     * @param array $expected
     * @dataProvider optionsProvider
     */
    public function testFinishView(array $files, array $options, array $expected)
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');
        $form     = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

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
        $this->assetHelper
            ->expects($this->exactly(count($files)))
            ->method('getUrl')
            ->will($this->returnValueMap($valueMap));

        $this->type->finishView($formView, $form, $options);
        $this->assertEquals($expected, $formView->vars);

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
            [
                [
                    'file1.txt',
                    'file2.txt',
                ],
                [
                    'source' => [
                        'path' => sys_get_temp_dir() . '/*.txt',
                        'url'  => 'download/files'
                    ],
                    'class'  => 'red'
                ],
                [
                    'value'  => null,
                    'attr'   => [],
                    'files'  => [
                        'file1.txt' => '/download/files/file1.txt',
                        'file2.txt' => '/download/files/file2.txt'
                    ],
                    'class'  => 'red'
                ]
            ]
        ];
    }
}
