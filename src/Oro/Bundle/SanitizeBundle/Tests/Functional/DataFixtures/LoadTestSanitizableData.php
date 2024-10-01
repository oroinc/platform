<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Entity\TestSanitizable;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadTestSanitizableData extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $testSanitizable = new TestSanitizable();

        $testSanitizable
            ->setFirstName('John')
            ->setMiddleName('Geoffrey')
            ->setLastName('Redison')
            ->setBirthday(new \DateTime('1970-01-01'))
            ->setEmail('john.redison@example.com')
            ->setEmailunguessable('john.redison.reserve@example.com')
            ->setPhone('1111-222')
            ->setPhoneSecond('3333-444')
            ->setSecret('secret')
            ->setTextSecret('text secret')
            ->setStateData(['key' => 'value']);
        $testSanitizable->email_third = 'john.redison.third@example.com';
        $testSanitizable->first_custom_field = 'sample string';
        $testSanitizable->email_wrong_type = 12345;
        $testSanitizable->custom_event_date = new \DateTime('1970-01-01');
        $testSanitizable->phone_third = '4444-333';
        $manager->persist($testSanitizable);

        $manager->flush();
    }
}
