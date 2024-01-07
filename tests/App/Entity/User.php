<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\Acme\Tests\App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    private ?int $id = null;
    private string $name;
    private string $email;
    private string $salt;

    public function __construct()
    {
        $this->salt = sha1(bin2hex('Liip'));
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function getUsername(): string
    {
        return $this->getName();
    }

    public function getUserIdentifier(): string
    {
        return $this->getName();
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): string
    {
        return $this->getSalt();
    }
}
