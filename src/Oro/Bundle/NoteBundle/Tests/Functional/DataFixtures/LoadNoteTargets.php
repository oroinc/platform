<?php

namespace Oro\Bundle\NoteBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

class LoadNoteTargets extends AbstractFixture
{
    const TARGET_ONE = 'note_target_1';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $noteTarget = new TestActivityTarget();
        $noteTarget->setString('test1');

        $manager->persist($noteTarget);
        $manager->flush();

        $this->addReference(self::TARGET_ONE, $noteTarget);
    }
}
