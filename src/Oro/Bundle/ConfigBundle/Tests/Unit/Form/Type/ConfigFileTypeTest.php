<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigFileType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Prophecy\Argument;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\HttpFoundation\File\File as HttpFile;
use Symfony\Component\Validator\Validation;

class ConfigFileTypeTest extends FormIntegrationTestCase
{
    const FILE1_ID = 1;
    const FILE2_ID = 2;

    /**
     * @var ConfigFileType
     */
    protected $formType;

    /**
     * @var ConfigFileDataTransformer
     */
    protected $transformer;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var HttpFile
     */
    protected $httpFile;

    protected function setUp()
    {
        $this->transformer = $this->prophesize(ConfigFileDataTransformer::class);
        $this->formType = new ConfigFileType($this->transformer->reveal());
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->formType);
    }

    public function testGetParent()
    {
        $this->assertEquals(FileType::class, $this->formType->getParent());
    }

    /**
     * @param mixed $defaultData
     * @param mixed $expectedData
     * @param mixed $submittedData
     * @param array $transformerArgs
     * @dataProvider submitDataProvider
     */
    public function testSubmit($defaultData, $expectedData, $submittedData, array $transformerArgs)
    {
        $this->addTransformerExpectations($transformerArgs);

        $form = $this->factory->create(ConfigFileType::class, $defaultData);
        $form->submit($submittedData);

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $this->file = new File();
        $this->httpFile = new HttpFile('test.php', false);

        return [
            'null' => [
                'defaultData' => null,
                'expectedData' => null,
                'submittedData' => null,
                'transformerArgs' => [null, null, Argument::type(File::class), null]
            ],
            'file' => [
                'defaultData' => self::FILE1_ID,
                'expectedData' => self::FILE1_ID,
                'submittedData' => ['file' => $this->httpFile],
                'transformerArgs' => [self::FILE1_ID, $this->file, $this->file, self::FILE1_ID]
            ]
        ];
    }

    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ConfigFileType::class => $this->formType
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param array $transformerArgs
     */
    protected function addTransformerExpectations(array $transformerArgs)
    {
        $this->transformer->setFileConstraints(Argument::type('array'))->shouldBeCalled();
        $this->transformer->transform($transformerArgs[0])->willReturn($transformerArgs[1]);
        $this->transformer->reverseTransform($transformerArgs[2])->willReturn($transformerArgs[3]);
    }
}
