<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\MigrationBundle\Entity\DataFixture;

class UpdateDataFixturesFixture extends AbstractFixture
{
    /**
     * @var array
     */
    protected $dataFixturesClassNames;

    /**
     * Set a list of data fixtures to be updated
     *
     * @param array $classNames
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
            foreach ($this->dataFixturesClassNames as $fixtureData) {
                $dataFixture = null;
                if (isset($fixtureData['version'])) {
                    $dataFixture = $manager
                        ->getRepository('OroMigrationBundle:DataFixture')
                        ->findOneBy(['className' => $fixtureData['fixtureClass']]);
                }
                if (!$dataFixture) {
                    $dataFixture = new DataFixture();
                    $dataFixture->setClassName($fixtureData['fixtureClass']);
                }

                $dataFixture
                    ->setVersion(isset($fixtureData['version']) ? $fixtureData['version'] : null)
                    ->setLoadedAt($loadedAt);
                $manager->persist($dataFixture);
            }
            $manager->flush();
        }
    }
}
