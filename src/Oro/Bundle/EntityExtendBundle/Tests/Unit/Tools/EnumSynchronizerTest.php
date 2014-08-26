<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;

class EnumSynchronizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dbTranslationMetadataCache;

    /** @var EnumSynchronizer */
    protected $synchronizer;

    public function setUp()
    {
        $this->configManager              = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine                   = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator                 = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->dbTranslationMetadataCache =
            $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
                ->disableOriginalConstructor()
                ->getMock();

        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->synchronizer = new EnumSynchronizer(
            $this->configManager,
            $this->doctrine,
            $this->translator,
            $this->dbTranslationMetadataCache
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $enumValueClassName must not be empty.
     */
    public function testApplyEnumEntityOptionsWithEmptyClassName()
    {
        $this->synchronizer->applyEnumEntityOptions('', false);
    }

    public function testApplyEnumEntityOptionsNoChanges()
    {
        $enumValueClassName = 'Test\EnumValue';
        $isPublic           = true;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('public', $isPublic);

        $enumConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumConfig));
        $this->configManager->expects($this->never())
            ->method('persist');

        $this->synchronizer->applyEnumEntityOptions($enumValueClassName, $isPublic);
    }

    public function testApplyEnumEntityOptionsNoFlush()
    {
        $enumValueClassName = 'Test\EnumValue';
        $isPublic           = false;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('public', !$isPublic);

        $enumConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumConfig));
        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($enumConfig));
        $this->configManager->expects($this->never())
            ->method('flush');

        $this->synchronizer->applyEnumEntityOptions($enumValueClassName, $isPublic, false);

        $this->assertEquals(
            $isPublic,
            $enumConfig->get('public')
        );
    }

    public function testApplyEnumEntityOptions()
    {
        $enumValueClassName = 'Test\EnumValue';
        $isPublic           = true;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));

        $enumConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumConfig));
        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($enumConfig));
        $this->configManager->expects($this->once())
            ->method('flush');

        $this->synchronizer->applyEnumEntityOptions($enumValueClassName, $isPublic);

        $this->assertEquals(
            $isPublic,
            $enumConfig->get('public')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $enumValueClassName must not be empty.
     */
    public function testApplyEnumOptionsWithEmptyClassName()
    {
        $this->synchronizer->applyEnumOptions('', [], 'en');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $locale must not be empty.
     */
    public function testApplyEnumOptionsWithEmptyLocale()
    {
        $this->synchronizer->applyEnumOptions('Test\EnumValue', [], null);
    }

    public function testApplyEnumOptionsEmpty()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale             = 'fr';

        $enumOptions = [];
        $values      = [];

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects($this->never())
            ->method('flush');
        $this->dbTranslationMetadataCache->expects($this->never())
            ->method('updateTimestamp');

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);
    }

    public function testApplyEnumOptionsNoChanges()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale             = 'fr';

        $enumOptions = [
            ['id' => 'opt1', 'label' => 'Option 1', 'priority' => 1, 'is_default' => true]
        ];
        $values      = [
            new TestEnumValue('opt1', 'Option 1', 1, true)
        ];

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects($this->never())
            ->method('flush');
        $this->dbTranslationMetadataCache->expects($this->never())
            ->method('updateTimestamp');

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);
    }

    public function testApplyEnumOptions()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale             = 'fr';

        $enumOptions = [
            ['id' => 'opt1', 'label' => 'Option 1', 'priority' => 1, 'is_default' => true],
            ['id' => 'opt2', 'label' => 'Option 2', 'priority' => 2, 'is_default' => false],
            ['id' => 'opt5', 'label' => 'Option 5', 'priority' => 3, 'is_default' => false],
            ['id' => '', 'label' => 'Option 4', 'priority' => 4, 'is_default' => true],
        ];

        $value1 = new TestEnumValue('opt1', 'Option 1', 1, true);
        $value2 = new TestEnumValue('opt2', 'Option 2 old', 4, true);
        $value3 = new TestEnumValue('opt3', 'Option 3', 3, false);
        $value5 = new TestEnumValue('opt5', 'Option 5', 2, false);

        $newValue = new TestEnumValue('opt4', 'Option 4', 4, true);

        $values = [$value1, $value2, $value3, $value5];

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($value3));
        $enumRepo->expects($this->once())
            ->method('createEnumValue')
            ->with('Option 4', 4, true)
            ->will($this->returnValue($newValue));
        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($newValue));

        $em->expects($this->once())
            ->method('flush');
        $this->dbTranslationMetadataCache->expects($this->once())
            ->method('updateTimestamp')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedValue1 = new TestEnumValue('opt1', 'Option 1', 1, true);
        $this->assertEquals($expectedValue1, $value1);
        $expectedValue2 = new TestEnumValue('opt2', 'Option 2', 2, false);
        $expectedValue2->setLocale($locale);
        $this->assertEquals($expectedValue2, $value2);
        $expectedValue5 = new TestEnumValue('opt5', 'Option 5', 3, false);
        $expectedValue5->setLocale($locale);
        $this->assertEquals($expectedValue5, $value5);
        $expectedNewValue = new TestEnumValue('opt4', 'Option 4', 4, true);
        $expectedNewValue->setLocale($locale);
        $this->assertEquals($expectedNewValue, $newValue);
    }

    public function testGetEnumOptions()
    {
        $enumValueClassName = 'Test\EnumValue';
        $values             = [['id' => 'opt1']];

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));
        $enumRepo = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumRepo));
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $enumRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['setHint', 'getArrayResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('select')
            ->with('e.id, e.priority, e.name as label, e.default as is_default')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.priority')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('setHint')
            ->with(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\Translatable\Query\TreeWalker\TranslationWalker'
            )
            ->will($this->returnSelf());
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($values));

        $result = $this->synchronizer->getEnumOptions($enumValueClassName);

        $this->assertEquals($values, $result);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $em
     * @param string                                   $enumValueClassName
     * @param string                                   $locale
     * @param array                                    $values
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values)
    {
        $enumRepo = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumRepo));
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $enumRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['setHint', 'getResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('setHint')
            ->with(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale)
            ->will($this->returnSelf());
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($values));

        return $enumRepo;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
