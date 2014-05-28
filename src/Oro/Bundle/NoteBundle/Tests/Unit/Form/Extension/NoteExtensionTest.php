<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\NoteBundle\Form\Extension\NoteExtension;
use Symfony\Component\Form\FormView;

class NoteExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var NoteExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $noteConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    public function setUp()
    {
        $noteConfig = new Config(new EntityConfigId('note', 'Oro\Bundle\UserBundle\Entity\User'));
        $noteConfig->set('enabled', 1);
        $this->noteConfigProvider = $this->getMockBuilder(
            'Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface'
        )
            ->setMockClassName('NoteConfigProvider')
            ->getMock();
        $this->noteConfigProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->will($this->returnValue($noteConfig));

        $extendConfig = new Config(new EntityConfigId('extend', 'Oro\Bundle\UserBundle\Entity\User'));
        $extendConfig->set(
            'relation',
            [
                'manyToOne|Oro\Bundle\NoteBundle\Entity\Note|Oro\Bundle\UserBundle\Entity\User|assoc_note_user' => [
                    'assign'          => false,
                    'field_id'        => false,
                    'owner'           => false,
                    'target_entity'   => 'Oro\Bundle\NoteBundle\Entity\Note',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Oro\Bundle\NoteBundle\Entity\Note',
                        'assoc_note_user',
                        'manyToOne'
                    )
                ]
            ]
        );
        $this->extendConfigProvider = $this->getMockBuilder(
            'Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface'
        )
            ->setMockClassName('ExtendConfigProvider')
            ->getMock();
        $this->extendConfigProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->will($this->returnValue($extendConfig));

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager
            ->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnCallback(
                    function ($param) {
                        return $this->getProviders($param);
                    }
                )
            );

        $this->extension = new NoteExtension($configManager);
    }

    public function testGetExtendedType()
    {
        $this->assertSame('note_choice', $this->extension->getExtendedType());
    }

    public function testBuildViewWithRelation()
    {
        $this->extendConfigProvider
            ->expects($this->any())
            ->method('getConfig')
            ->with('Oro\Bundle\NoteBundle\Entity\Note')
            ->will(
                $this->returnCallback(
                    function () {
                        $config = new Config(new EntityConfigId('extend', 'Oro\Bundle\NoteBundle\Entity\Note'));
                        $config->set(
                            'relation',
                            [
                                'manyToOne|Oro\Bundle\NoteBundle\Entity\Note|' .
                                'Oro\Bundle\UserBundle\Entity\User|assoc_note_user' => [
                                    'assign' => true
                                ]
                            ]
                        );

                        return $config;
                    }
                )
            );

        $view     = $this->getView();
        $expected = [
            'attr' => ['class' => 'disabled-choice'],
        ];
        foreach ($expected as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($value, $view->vars[$option]);
        }
    }

    public function testBuildViewWithRelationAndCssClass()
    {
        $this->extendConfigProvider
            ->expects($this->any())
            ->method('getConfig')
            ->with('Oro\Bundle\NoteBundle\Entity\Note')
            ->will(
                $this->returnCallback(
                    function () {
                        $config = new Config(new EntityConfigId('extend', 'Oro\Bundle\NoteBundle\Entity\Note'));
                        $config->set(
                            'relation',
                            [
                                'manyToOne|Oro\Bundle\NoteBundle\Entity\Note|' .
                                'Oro\Bundle\UserBundle\Entity\User|assoc_note_user' => [
                                    'assign' => true
                                ]
                            ]
                        );

                        return $config;
                    }
                )
            );

        $view     = $this->getView(['attr' => ['class' => 'test']]);
        $expected = [
            'attr' => ['class' => 'test disabled-choice']
        ];
        foreach ($expected as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($value, $view->vars[$option]);
        }
    }

    public function testBuildViewWithOutRelation()
    {
        $this->extendConfigProvider
            ->expects($this->any())
            ->method('getConfig')
            ->with('Oro\Bundle\NoteBundle\Entity\Note')
            ->will(
                $this->returnCallback(
                    function () {
                        $config = new Config(new EntityConfigId('extend', 'Oro\Bundle\NoteBundle\Entity\Note'));
                        $config->set(
                            'relation',
                            [
                                'manyToOne|Oro\Bundle\NoteBundle\Entity\Note|' .
                                'Oro\Bundle\UserBundle\Entity\User|assoc_note_user' => [
                                    'assign' => false
                                ]
                            ]
                        );

                        return $config;
                    }
                )
            );

        $view     = $this->getView();
        $expected = [
            'attr' => []
        ];
        foreach ($expected as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($value, $view->vars[$option]);
        }
    }

    /**
     * @param array $vars
     *
     * @return FormView
     */
    protected function getView($vars = [])
    {
        $options = [
            'config_id' => new EntityConfigId('note', 'Oro\Bundle\UserBundle\Entity\User')
        ];

        $view = new FormView();

        if ($vars) {
            $view->vars = array_merge($view->vars, $vars);
        }

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('enabled'));

        $this->extension->buildView($view, $form, $options);

        return $view;
    }

    /**
     * @param $param
     * @return null|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProviders($param)
    {
        $provider = null;
        switch ($param) {
            case 'note':
                $provider = $this->noteConfigProvider;
                break;
            case 'extend':
                $provider = $this->extendConfigProvider;
                break;
        }

        return $provider;
    }
}
