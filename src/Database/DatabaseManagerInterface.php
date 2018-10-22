<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Database;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\FunctionalTestBundle\Database\Backup\DatabaseBackupInterface;
use Liip\FunctionalTestBundle\Database\Tools\DatabaseToolInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
interface DatabaseManagerInterface
{
    public function setPurgeMode(int $purgeMode = null): void;

    public function setExcludedDoctrineTables(array $excludedDoctrineTables): void;

    public function setPostFixtureSetupCallback(callable $callback): void;

    public function setPreFixtureBackupRestoreCallback(callable $callback): void;

    public function setPostFixtureBackupRestoreCallback(callable $callback): void;

    public function setPreReferenceSaveCallback(callable $callback): void;

    public function setPostReferenceSaveCallback(callable $callback): void;

    public function isDatabaseExists(): bool;

    public function createDatabase(): void;

    public function dropDatabase(): void;

    public function createSchema(): void;

    public function dropSchema(): void;

    public function updateSchema(): void;

    public function loadFixtures(array $classNames, bool $append = false): AbstractExecutor;

    public function loadAliceFixture(array $paths = [], bool $append = false): array;

    public function purgeData(): void;

    public function isBackupExists(): bool;

    public function backup(): void;

    public function restore(): AbstractExecutor;
}