<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * AnonymousToken represents an anonymous token.
 *
 * Copyright (c) 2004-present Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */
class AnonymousToken extends AbstractToken
{
    private string $secret;

    /**
     * @param string $secret
     * @param string|\Stringable|UserInterface $user
     * @param string[] $roles
     */
    public function __construct(string $secret, $user, array $roles = [])
    {
        parent::__construct($roles);

        $this->secret = $secret;
        $this->setUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(): mixed
    {
        return '';
    }

    /**
     * Returns the secret.
     *
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * {@inheritdoc}
     */
    public function __serialize(): array
    {
        return [$this->secret, parent::__serialize()];
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize(array $data): void
    {
        [$this->secret, $parentData] = $data;
        $parentData = \is_array($parentData) ? $parentData : unserialize($parentData);
        parent::__unserialize($parentData);
    }
}
