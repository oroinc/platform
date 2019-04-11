<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;

class EmailHolderTestEntity implements EmailHolderInterface
{
    /** @var string */
    private $testField;

    /**
     * @return string
     */
    public function getTestField(): string
    {
        return $this->testField;
    }

    /**
     * @param string $testField
     */
    public function setTestField(string $testField): void
    {
        $this->testField = $testField;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): string
    {
        return 'test@test.com';
    }
}
