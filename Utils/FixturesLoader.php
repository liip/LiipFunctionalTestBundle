<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\DBAL\Connection;
use Nelmio\Alice\Fixtures;
use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;

class FixturesLoader
{
    /**
     * @param string $type
     *
     * @return string
     */
    public static function getExecutorClass($type)
    {
        return 'PHPCR' === $type && class_exists('Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor')
            ? 'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor'
            : 'Doctrine\\Common\\DataFixtures\\Executor\\'.$type.'Executor';
    }

    /**
     * Get file path of the SQLite database.
     *
     * @param Connection $connection
     *
     * @return string $name
     */
    public static function getNameParameter(Connection $connection)
    {
        $params = $connection->getParams();

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
     * This function finds the time when the data blocks of a class definition
     * file were being written to, that is, the time when the content of the
     * file was changed.
     *
     * @param string $class The fully qualified class name of the fixture class to
     *                      check modification date on.
     *
     * @return \DateTime|null
     */
    protected static function getFixtureLastModified($class)
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
     * @param array  $classNames The fixture classnames to check
     * @param string $backup     The fixture backup SQLite database file path
     *
     * @return bool TRUE if the backup was made since the modifications to the
     *              fixtures; FALSE otherwise
     */
    public static function isBackupUpToDate(array $classNames, $backup, $container)
    {
        $backupLastModifiedDateTime = new \DateTime();
        $backupLastModifiedDateTime->setTimestamp(filemtime($backup));

        /** @var \Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader $loader */
        $loader = self::getFixtureLoader($container, $classNames);

        // Use loader in order to fetch all the dependencies fixtures.
        foreach ($loader->getFixtures() as $className) {
            $fixtureLastModifiedDateTime = self::getFixtureLastModified($className);
            if ($backupLastModifiedDateTime < $fixtureLastModifiedDateTime) {
                return false;
            }
        }

        return true;
    }

    /**
     * Locate fixture files.
     *
     * @param array $paths
     *
     * @return array $files
     */
    public static function locateResources($paths, $container)
    {
        $files = array();

        $kernel = $container->get('kernel');

        foreach ($paths as $path) {
            if ($path[0] !== '@' && file_exists($path) === true) {
                $files[] = $path;
                continue;
            }

            $files[] = $kernel->locateResource($path);
        }

        return $files;
    }

    /**
     * Retrieve Doctrine DataFixtures loader.
     *
     * @param ContainerInterface $container
     * @param array              $classNames
     *
     * @return Loader
     */
    public static function getFixtureLoader(ContainerInterface $container, array $classNames)
    {
        $loaderClass = class_exists('Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader')
            ? 'Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader'
            : (class_exists('Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader')
                // This class is not available during tests.
                // @codeCoverageIgnoreStart
                ? 'Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader'
                // @codeCoverageIgnoreEnd
                : 'Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader');

        $loader = new $loaderClass($container);

        foreach ($classNames as $className) {
            self::loadFixtureClass($loader, $className);
        }

        return $loader;
    }

    /**
     * Purge database.
     *
     * @param ObjectManager            $om
     * @param string                   $type
     * @param int                      $purgeMode
     * @param string                   $executorClass
     * @param ProxyReferenceRepository $referenceRepository
     */
    public static function purgeDatabase(ObjectManager $om, $type, $purgeMode,
         $executorClass,
         ProxyReferenceRepository $referenceRepository,
         $container)
    {
        $purgerClass = 'Doctrine\\Common\\DataFixtures\\Purger\\'.$type.'Purger';
        if ('PHPCR' === $type) {
            $purger = new $purgerClass($om);
            $initManager = $container->has('doctrine_phpcr.initializer_manager')
                ? $container->get('doctrine_phpcr.initializer_manager')
                : null;

            $executor = new $executorClass($om, $purger, $initManager);
        } else {
            $purger = new $purgerClass();
            if (null !== $purgeMode) {
                $purger->setPurgeMode($purgeMode);
            }

            $executor = new $executorClass($om, $purger);
        }

        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();

        return $executor;
    }

    /**
     * Load a data fixture class.
     *
     * @param Loader $loader
     * @param string $className
     */
    protected static function loadFixtureClass($loader, $className)
    {
        $fixture = new $className();

        if ($loader->hasFixture($fixture)) {
            unset($fixture);

            return;
        }

        $loader->addFixture($fixture);

        if ($fixture instanceof DependentFixtureInterface) {
            foreach ($fixture->getDependencies() as $dependency) {
                self::loadFixtureClass($loader, $dependency);
            }
        }
    }
}
