<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Database\Tools;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
interface DatabaseToolInterface
{
    public function getType(): string;

    public function getDriverName(): string;

    public function getExecutor(bool $append = false): AbstractExecutor;

    public function getPurger(): PurgerInterface;

    public function purgeData(): void;

    public function getDatabasesList(): array;

    public function createDatabase(): void;

    public function dropDatabase(): void;

    public function isDatabaseExists(): bool;

    public function createSchema(): void;

    public function dropSchema(): void;

    public function updateSchema(): void;
}
