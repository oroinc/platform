<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\MutableEntityOverrideProvider;
use PHPUnit\Framework\TestCase;

class MutableEntityOverrideProviderTest extends TestCase
{
    private MutableEntityOverrideProvider $entityOverrideProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityOverrideProvider = new MutableEntityOverrideProvider(['Test\Entity1' => 'Test\Entity2']);
    }

    public function testGetSubstituteEntityClassWhenSubstitutionExists(): void
    {
        self::assertEquals(
            'Test\Entity2',
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity1')
        );
    }

    public function testGetSubstituteEntityClassWhenSubstitutionDoesNotExist(): void
    {
        self::assertNull(
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity2')
        );
    }

    public function testGetEntityClassWhenSubstitutionExists(): void
    {
        self::assertEquals(
            'Test\Entity1',
            $this->entityOverrideProvider->getEntityClass('Test\Entity2')
        );
    }

    public function testGetEntityClassWhenSubstitutionDoesNotExist(): void
    {
        self::assertNull(
            $this->entityOverrideProvider->getEntityClass('Test\Entity1')
        );
    }

    public function testAddSubstitution(): void
    {
        $this->entityOverrideProvider->addSubstitution('Test\Entity3', 'Test\Entity4');
        self::assertEquals(
            'Test\Entity4',
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity3')
        );
        self::assertEquals(
            'Test\Entity3',
            $this->entityOverrideProvider->getEntityClass('Test\Entity4')
        );
    }
}
