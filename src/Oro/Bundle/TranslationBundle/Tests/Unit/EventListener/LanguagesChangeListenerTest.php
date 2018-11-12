<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\TranslationBundle\EventListener\LanguagesChangeListener;

class LanguagesChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var ConfigValueRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var LanguagesChangeListener */
    protected $listener;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->repository = $this->createMock(ConfigValueRepository::class);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())->method('getRepository')->willReturn($this->repository);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry */
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())->method('getManagerForClass')->willReturn($entityManager);

        $this->listener = new LanguagesChangeListener($this->configManager, $managerRegistry);
    }

    /**
     * @dataProvider onConfigUpdateDataProvider
     *
     * @param array $availableCodes
     * @param array $codes
     * @param $expectedUpdateCount
     * @param bool $isChanged
     * @param bool $isGlobal
     */
    public function testOnConfigUpdate(
        array $availableCodes,
        array $codes,
        $expectedUpdateCount,
        $isChanged = true,
        $isGlobal = true
    ) {
        $event = $this->getEvent($availableCodes, $isChanged, $isGlobal);

        $configValues = [];
        $config = new Config();
        $config->setRecordId(1);
        foreach ($codes as $code) {
            $configValue = new ConfigValue();
            $configValue->setValue($code)->setConfig($config);
            $configValues[] = $configValue;
        }
        $this->repository->expects($this->exactly((int) ($isChanged && $isGlobal)))
            ->method('getConfigValues')
            ->willReturn($configValues);

        $this->configManager->expects($this->exactly($expectedUpdateCount))->method('reset');
        $this->configManager->expects($this->exactly($expectedUpdateCount))->method('flush');

        $this->listener->onConfigUpdate($event);
    }

    /**
     * @return array
     */
    public function onConfigUpdateDataProvider()
    {
        return [
            'not changed' => [
                'availableCodes' => ['en_US'],
                'codes' => ['de_DE'],
                'expectedUpdateCount' => 0,
                'isChanged' => false,
                'isGlobal' => true,
            ],
            'not global' => [
                'availableCodes' => ['en_US'],
                'codes' => ['en_US'],
                'expectedUpdateCount' => 0,
                'isChanged' => true,
                'isGlobal' => false,
            ],
            'no updates' => [
                'availableCodes' => ['en_US'],
                'codes' => ['en_US'],
                'expectedUpdateCount' => 0,
            ],
            'updates' => [
                'availableCodes' => ['en_US'],
                'codes' => ['de_DE', 'en_US', 'fr_FR'],
                'expectedUpdateCount' => 2,
            ],
        ];
    }

    /**
     * @param array $availableCodes
     * @param bool $isChanged
     * @param bool $isGlobal
     *
     * @return ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEvent(array $availableCodes, $isChanged = true, $isGlobal = true)
    {
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_locale.languages')
            ->willReturn($isChanged);

        $event->expects($this->exactly((int) ($isChanged && $isGlobal)))
            ->method('getNewValue')
            ->with('oro_locale.languages')
            ->willReturn($availableCodes);

        $event->expects($this->exactly((int) $isChanged))
            ->method('getScope')
            ->willReturn($isGlobal ? 'global' : '');

        return $event;
    }
}
