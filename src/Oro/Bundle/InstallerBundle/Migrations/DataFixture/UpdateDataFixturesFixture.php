<?php

namespace Oro\Bundle\InstallerBundle\Migrations\DataFixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\InstallerBundle\Entity\DataFixture;

class UpdateDataFixturesFixture extends AbstractFixture
{
    /**
     * @var string[]
     */
    protected $dataFixturesClassNames;

    /**
     * Set a list of data fixtures to be updated
     *
     * @param string[] $classNames
     */
    public function setDataFixtures($classNames)
    {
        $this->dataFixturesClassNames = $classNames;
    }

    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
    {
        if (!empty($this->dataFixturesClassNames)) {
            $loadedAt = new \DateTime('now', new \DateTimeZone('UTC'));
            foreach ($this->dataFixturesClassNames as $className) {
                $dataFixture = new DataFixture();
                $dataFixture
                    ->setClassName($className)
                    ->setLoadedAt($loadedAt);
                $manager->persist($dataFixture);
            }
            $manager->flush();
        }
    }
}
