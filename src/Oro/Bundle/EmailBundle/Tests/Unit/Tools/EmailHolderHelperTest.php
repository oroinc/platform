<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestCustomEntity;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class EmailHolderHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var EmailHolderHelper */
    protected $helper;

    protected function setUp()
    {
        $this->extendConfigProvider = $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');

        $this->helper = new EmailHolderHelper($this->extendConfigProvider);
    }

    /**
     * @dataProvider hasEmailProvider
     */
    public function testHasEmail($objectOrClassName, $expected)
    {
        $this->assertEquals($expected, $this->helper->hasEmail($objectOrClassName));
    }

    /**
     * @dataProvider getEmailProvider
     */
    public function testGetEmail($object, $expected)
    {
        $this->assertEquals($expected, $this->helper->getEmail($object));
    }

    public function testGetEmailFromRelatedObjectNotConfigurableEntity()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user@example.com'));
        $object->setEmailHolder(new TestEmailHolder('test@example.com'));

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->will($this->returnValue(false));
        $this->extendConfigProvider->expects($this->never())
            ->method('getConfig');

        $this->assertEquals(null, $this->helper->getEmail($object));
    }

    public function testGetEmailFromRelatedObjectNoTargetEntities()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user@example.com'));
        $object->setEmailHolder(new TestEmailHolder('test@example.com'));
        $object->setOther(new SomeEntity());

        $config = new Config(new EntityConfigId('extend', get_class($object)));
        $config->set(
            'relation',
            [
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'user', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser'
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'emailHolder', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder'
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'other', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity'
                ],
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->will($this->returnValue(true));
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(get_class($object))
            ->will($this->returnValue($config));

        $this->assertEquals(null, $this->helper->getEmail($object));
    }

    public function testGetEmailFromRelatedObject()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user@example.com'));
        $object->setEmailHolder(new TestEmailHolder('test@example.com'));
        $object->setOther(new SomeEntity());

        $config = new Config(new EntityConfigId('extend', get_class($object)));
        $config->set(
            'relation',
            [
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'user', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser'
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'emailHolder', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder'
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'other', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity'
                ],
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->will($this->returnValue(true));
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(get_class($object))
            ->will($this->returnValue($config));

        $this->helper->addTargetEntity('Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser');
        $this->helper->addTargetEntity('Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder', -10);

        $this->assertEquals('test@example.com', $this->helper->getEmail($object));
    }

    public function hasEmailProvider()
    {
        return array(
            'null'                                => array(null, false),
            'not obj and not str'                 => array(123, false),
            'obj without email'                   => array(new \stdClass(), false),
            'obj implements EmailHolderInterface' => array(new TestEmailHolder(), true),
            'obj has getEmail method'             => array(new TestUser(), true),
            'cls without email'                   => array('stdClass', false),
            'cls implements EmailHolderInterface' => array(
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder',
                true
            ),
            'cls has getEmail method'             => array(
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser',
                true
            ),
        );
    }

    public function getEmailProvider()
    {
        return array(
            'null'                                => array(null, null),
            'not obj'                             => array(
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder',
                null
            ),
            'obj implements EmailHolderInterface' => array(
                new TestEmailHolder('test@example.com'),
                'test@example.com'
            ),
            'obj has getEmail method'             => array(
                new TestUser('test@example.com'),
                'test@example.com'
            ),
        );
    }
}
