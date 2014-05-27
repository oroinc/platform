<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigScopeType;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\NoteBundle\Form\Type\NoteEnabledChoiceType;
use Oro\Bundle\NoteBundle\Form\Extension\NoteExtension;

class NoteEnabledChoiceTypeTest extends TypeTestCase
{
    /** @var NoteEnabledChoiceType */
    protected $type;

    protected $configManager;

    protected function setUp()
    {
        $noteConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface')
            ->setMockClassName('NoteConfigProvider')
            ->getMock();

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface')
            ->setMockClassName('ExtendConfigProvider')
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager
            ->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnCallback(
                    function ($param) use ($noteConfigProvider, $extendConfigProvider) {
                        $provider = null;
                        switch ($param) {
                            case 'note':
                                $provider = $noteConfigProvider;
                                break;
                            case 'extend':
                                $provider = $extendConfigProvider;
                                break;
                        }

                        return $provider;
                    }
                )
            );


//        $this->factory = Forms::createFormFactoryBuilder()
//            ->addTypeExtension(new NoteExtension($this->configManager))
//            ->getFormFactory();

        $this->type = new NoteEnabledChoiceType();
    }

    protected function getExtensions()
    {
        $transCache = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();
        $trans = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $childType = new ConfigType(
            $this->configManager,
            $trans,
            $transCache
        );

        return [
            new PreloadedExtension(
                [
                    $childType->getName() => $childType,
                ],
                []
            )
        ];
    }


    public function testNames()
    {
        $this->assertEquals('note_choice', $this->type->getName());
        $this->assertEquals('choice', $this->type->getParent());
    }

    public function testType()
    {
        $formData = [
            'enabled' => 1
        ];
        $formOptions = [
            //'class_name' => 'Oro\Bundle\UserBundle\Entity\User'
            //block:          other
            'required' =>   true,
            'label' =>      'Can add notes',
            'choices' =>     ['No', 'Yes'],
            'empty_value' => false,
            'empty_data' =>  'No',
            //tooltip:        'After you enable this option it cannot be disabled.'
            //'config_id'  => true
        ];

        $type = new NoteEnabledChoiceType();

        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new NoteExtension($this->configManager))
            ->getFormFactory();

        $form = $this->factory->create($type, null, $formOptions);

        $form->submit($formData);

        $this->assertTrue($form->isSubmitted());

        $formView = $form->createView();


        //$event = new FormEvent($form, $formData);

        //$this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        //$this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);

        //$this->builder->
        //$this->factory->
        $a = 1;


//        $form->submit($formData);

//        $object = new EntityConfigModel();
//        $object->setClassName('NewEntityClassName');


//        $this->assertEquals($object, $form->getData());
    }
}
