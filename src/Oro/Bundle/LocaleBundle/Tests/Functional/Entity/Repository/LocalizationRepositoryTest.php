<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class LocalizationRepositoryTest extends WebTestCase
{
    /** @var LocalizationRepository */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizations'
            ]
        );

        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroLocaleBundle:Localization');
    }

    public function testGetLocalizationsCount()
    {
        $result = $this->repository->getLocalizationsCount();

        $this->assertInternalType('int', $result);
        $this->assertEquals(3, $result);
    }
}
