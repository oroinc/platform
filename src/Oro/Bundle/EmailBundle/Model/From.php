<?php

namespace Oro\Bundle\EmailBundle\Model;

use Symfony\Component\Mime\Address as SymfonyAddress;

/**
 * Model that stores "From" email address and name.
 */
class From
{
    private string $address;

    private string $name;

    private function __construct(string $address, string $name = '')
    {
        $this->address = $address;
        $this->name = $name;
    }

    public static function emailAddress(From|string $emailAddress, string $name = ''): self
    {
        if ($emailAddress instanceof self) {
            return self::emailAddress($emailAddress->toString(), $name);
        }

        $symfonyAddress = SymfonyAddress::create($emailAddress);

        return new self($symfonyAddress->getAddress(), $name ?: $symfonyAddress->getName());
    }

    public function getAddress(): string
    {
        return trim($this->address);
    }

    public function getName(): string
    {
        return trim(str_replace(["\n", "\r"], '', $this->name));
    }

    public function toArray(): array
    {
        return [$this->getAddress(), $this->getName()];
    }

    public function toString(): string
    {
        $name = $this->getName();
        $address = $this->getAddress();

        return $name ? sprintf('"%s" <%s>', $name, $address) : $address;
    }
}
