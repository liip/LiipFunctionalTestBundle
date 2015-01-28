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

use Symfony\Component\DependencyInjection\Container;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;

class TestDatabaseCache
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getCachedExecutor(TestDatabasePreparator $dbPreparator, array $classNames)
    {
        $backup = $this->buildCacheFilePath($dbPreparator->getMetaDatas(), $classNames);
        if ($this->isBackupUpToDate($classNames, $backup)) {
            $om = $dbPreparator->getObjectManager();
            $om->flush();
            $om->clear();

            $executor = $dbPreparator->getExecutorWithReferenceRepository();
            $executor->getReferenceRepository()->load($backup);

            copy($backup, $this->getSQLiteName($om->getConnection()->getParams()));

            return $executor;
        }
    }

    public function storeToCache(AbstractExecutor $executor, TestDatabasePreparator $dbPreparator, array $classNames)
    {
        $backup = $this->buildCacheFilePath($dbPreparator->getMetaDatas(), $classNames);
        $executor->getReferenceRepository()->save($backup);
        copy($this->getSQLiteName($dbPreparator->getObjectManager()->getConnection()->getParams()), $backup);
    }

    /**
     * This function finds the time when the data blocks of a class definition
     * file were being written to, that is, the time when the content of the
     * file was changed.
     *
     * @param string $class
     *            The fully qualified class name of the fixture class to
     *            check modification date on.
     *
     * @return \DateTime|null
     */
    protected function getClassLastModified($class)
    {
        $lastModifiedDateTime = null;

        $reflClass = new \ReflectionClass($class);
        $classFileName = $reflClass->getFileName();

        if (file_exists($classFileName)) {
            $lastModifiedDateTime = new \DateTime();
            $lastModifiedDateTime->setTimestamp(filemtime($classFileName));
        }

        return $lastModifiedDateTime;
    }

    /**
     * Determine if the Fixtures that define a database backup have been
     * modified since the backup was made.
     *
     * @param array $classNames
     *            The fixture classnames to check
     * @param string $backup
     *            The fixture backup SQLite database file path
     *
     * @return bool TRUE if the backup was made since the modifications to the
     *         fixtures; FALSE otherwise
     */
    private function isBackupUpToDate(array $classNames, $backup)
    {
        if(!file_exists($backup) || !file_exists($backup.'.ser')) {
            return false;
        }

        $backupLastModifiedDateTime = new \DateTime();
        $backupLastModifiedDateTime->setTimestamp(filemtime($backup));

        foreach ($classNames as &$className) {
            $fixtureLastModifiedDateTime = $this->getClassLastModified($className);
            if ($backupLastModifiedDateTime < $fixtureLastModifiedDateTime) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @param array $metadatas
     * @param array $classNames
     * @return string full path to the database cache file
     */
    private function buildCacheFilePath(array $metadatas, array $classNames)
    {
        return $this->container->getParameter('kernel.cache_dir') . '/test_' . md5(serialize($metadatas) . serialize($classNames)) . '.db';
    }

    /**
     * Get Filename of file base SQLite database
     *
     * @param array $params see Doctrine\DBAL\Connection->getParams()
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getSQLiteName(array $params)
    {
        if (isset($params['master'])) {
            $params = $params['master'];
        }

        $name = isset($params['path']) ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);
        if (!$name) {
            throw new \InvalidArgumentException("Connection does not contain a 'path' or 'dbname' parameter and cannot be dropped.");
        }

        return $name;
    }

    /**
     * Is caching of SQLite Database instances enabled?
     *
     * Caching can be enabled in app/config/config_test.yml:
     * ``` yaml
     * liip_functional_test:
     *     cache_sqlite_db: true
     * ```
     */
    public function isCacheEnabled()
    {
        return $this->container->getParameter('liip_functional_test.cache_sqlite_db');
    }
}