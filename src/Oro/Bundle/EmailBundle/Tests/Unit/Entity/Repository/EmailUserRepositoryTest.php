<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Repository;

use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class EmailUserRepositoryTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));
    }

    public function testSetEmailUsersSeen(): void
    {
        $ids = [];
        $firstBatch = [];
        $secondBatch = [];
        $j = 1;
        for ($i = 1; $i <= 250; $i += 2) {
            $ids[] = $i;
            if ($j <= 100) {
                $firstBatch[] = $i;
            } else {
                $secondBatch[] = $i;
            }
            $j++;
        }

        $this->addQueryExpectation(
            'UPDATE oro_email_user SET is_seen = ? WHERE id IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,'
            . ' ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,'
            . ' ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,'
            . ' ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AND unsyncedFlagCount = 0',
            null,
            [1 => true, ...$firstBatch],
            [1 => ParameterType::BOOLEAN, ...array_fill(1, count($firstBatch), ParameterType::INTEGER)],
            count($firstBatch)
        );
        $this->addQueryExpectation(
            'UPDATE oro_email_user SET is_seen = ? WHERE id IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,'
            . ' ?, ?, ?, ?, ?, ?, ?) AND unsyncedFlagCount = 0',
            null,
            [1 => true, ...$secondBatch],
            [1 => ParameterType::BOOLEAN, ...array_fill(1, count($secondBatch), ParameterType::INTEGER)],
            count($secondBatch)
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        /** @var EmailUserRepository $repo */
        $repo = $this->em->getRepository(EmailUser::class);
        $repo->setEmailUsersSeen($ids, true);
    }
}
