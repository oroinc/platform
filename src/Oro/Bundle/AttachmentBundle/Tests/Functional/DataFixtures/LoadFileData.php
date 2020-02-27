<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Creates 4 File entities
 */
class LoadFileData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 4; $i++) {
            $filename = sprintf('file-%s', $i);
            $file = new File();
            $file->setFilename($filename);
            $manager->persist($file);
            $this->setReference($filename, $file);
        }

        $manager->flush();
    }
}
