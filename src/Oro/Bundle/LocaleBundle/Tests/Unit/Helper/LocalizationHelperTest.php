<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class LocalizationHelperTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\LocaleBundle\Entity\Localization';

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var LocalizationRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var LocalizationHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->repository = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->manager);

        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->repository);

        $this->helper = new LocalizationHelper($this->registry);
        $this->helper->setEntityClass(self::ENTITY_CLASS);
    }

    /**
     * @param Localization[] $inputData
     * @param Localization $expectedData
     *
     * @dataProvider getCurrentLocalizationProvider
     */
    public function testGetCurrentLocalization(array $inputData, Localization $expectedData)
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($inputData);

        $this->assertEquals($expectedData, $this->helper->getCurrentLocalization());
    }

    /**
     * @return array
     */
    public function getCurrentLocalizationProvider()
    {
        return [
            'existing "en"' => [
                'input' => [
                    (new Localization())->setLanguageCode('en'),
                    (new Localization())->setLanguageCode('en_US'),
                ],
                'expected' => (new Localization())->setLanguageCode('en'),
            ],
            'without "en"' => [
                'input' => [
                    (new Localization())->setLanguageCode('en_US'),
                    (new Localization())->setLanguageCode('en_CA'),
                ],
                'expected' => (new Localization())->setLanguageCode('en_US'),
            ],
        ];
    }

    public function testGetAll()
    {
        $items = [new Localization()];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($items);

        $this->assertSame($items, $this->helper->getAll());
    }
}
