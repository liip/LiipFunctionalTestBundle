<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User.
 */
class User implements UserInterface
{
    // Properties which will be serialized have to be "protected"
    // @see http://stackoverflow.com/questions/9384836/symfony2-serialize-entity-object-to-session/10014802#10014802

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $salt;

    /**
     * @var string
     */
    private $algorithm;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var string
     */
    private $confirmationToken;

    public function __construct()
    {
        $this->salt = sha1(
            // http://php.net/manual/fr/function.openssl-random-pseudo-bytes.php
            bin2hex(openssl_random_pseudo_bytes(100))
        );
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return User
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set salt.
     *
     * @param string $salt
     *
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt.
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set algorithm.
     *
     * @param string $algorithm
     *
     * @return User
     */
    public function setAlgorithm($algorithm)
    {
        $this->algorithm = $algorithm;

        return $this;
    }

    /**
     * Get algorithm.
     *
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    /**
     * Set enabled.
     *
     * @param bool $enabled
     *
     * @return User
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled.
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set confirmationToken.
     *
     * @param string $confirmationToken
     *
     * @return User
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * Get confirmationToken.
     *
     * @return string
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    // Functions required for compatibility with UserInterface
    // @see http://symfony.com/doc/2.3/cookbook/security/custom_provider.html

    public function getRoles()
    {
        return array('ROLE_ADMIN');
    }

    public function getUsername()
    {
        return $this->getName();
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->id !== $user->getId()) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        return true;
    }

    // @see http://stackoverflow.com/questions/9384836/symfony2-serialize-entity-object-to-session/19133985#19133985

    public function __sleep()
    {
        // these are field names to be serialized, others will be excluded
        // but note that you have to fill other field values by your own
        return array('id', 'name', 'password', 'salt');
    }
}
