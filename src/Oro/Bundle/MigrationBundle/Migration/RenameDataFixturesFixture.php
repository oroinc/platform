<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Entity\DataFixture;

/**
 * Change class names for renamed data fixtures
 */
class RenameDataFixturesFixture extends AbstractFixture
{
    /** @var string[] */
    private $renamedDataFixtures = [];

    public function addRename(string $previousClassName, string $currentClassName): void
    {
        $this->renamedDataFixtures[$previousClassName] = $currentClassName;
    }

    public function isNeedPerform(): bool
    {
        return (bool)$this->renamedDataFixtures;
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        if (empty($this->renamedDataFixtures)) {
            return;
        }
        /** @var DataFixture[] $dataFixtures */
        $dataFixtures = $manager->getRepository(DataFixture::class)
            ->findBy(['className' => \array_keys($this->renamedDataFixtures)]);

        foreach ($dataFixtures as $dataFixture) {
            $dataFixture->setClassName($this->renamedDataFixtures[$dataFixture->getClassName()]);
        }

        $manager->flush();
    }
}
