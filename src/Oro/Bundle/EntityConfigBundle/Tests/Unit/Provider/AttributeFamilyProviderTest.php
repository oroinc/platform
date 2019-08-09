<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeFamilyProvider;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\AttributeFamilyStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributeFamilyProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AttributeFamilyRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var AttributeFamilyProvider */
    private $provider;

    protected function setUp()
    {
        $this->repository = $this->createMock(AttributeFamilyRepository::class);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine */
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(AttributeFamily::class)
            ->willReturn($this->repository);

        $this->provider = new AttributeFamilyProvider($doctrine);
    }

    public function testGetAvailableAttributeFamilies(): void
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['entityClass' => \stdClass::class])
            ->willReturn([$this->createAttributeFamily(1001, 'Test1'), $this->createAttributeFamily(2002, 'Test2')]);

        $this->assertEquals(
            ['Test1' => 1001, 'Test2' => 2002],
            $this->provider->getAvailableAttributeFamilies(\stdClass::class)
        );

        // second call must check values from local cache
        $this->assertEquals(
            ['Test1' => 1001, 'Test2' => 2002],
            $this->provider->getAvailableAttributeFamilies(\stdClass::class)
        );
    }

    /**
     * @param int $id
     * @param string $defaultLabel
     * @return AttributeFamily
     */
    private function createAttributeFamily(int $id, string $defaultLabel): AttributeFamily
    {
        $label = new LocalizedFallbackValue();
        $label->setString($defaultLabel);

        return $this->getEntity(AttributeFamilyStub::class, ['id' => $id, 'labels' => new ArrayCollection([$label])]);
    }
}
