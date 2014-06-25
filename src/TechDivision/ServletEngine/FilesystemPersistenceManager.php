<?php

/**
 * TechDivision\ServletEngine\FilesystemPersistenceManager
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
namespace TechDivision\ServletEngine;

use \TechDivision\Servlet\ServletSession;
use \TechDivision\Storage\StorageInterface;
use \TechDivision\Storage\StackableStorage;
use \TechDivision\ServletEngine\SessionFilter;

/**
 * A thread thats preinitialized session instances and adds them to the
 * the session pool.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class FilesystemPersistenceManager extends \Thread implements PersistenceManager
{

    /**
     * The sessions we want to handle persistence for.
     *
     * @var \TechDivision\Storage\StorageInterface
     */
    protected $sessions;

    /**
     * The storage for the session checksums.
     *
     * @return \TechDivision\Storage\StorageInterface
     */
    protected $checksums;

    /**
     * The session marshaller instance we use to marshall/unmarschall sessions.
     *
     * @var \TechDivision\ServletEngine\SessionMarshaller
     */
    protected $sessionMarshaller;

    /**
     * The session factory instance.
     *
     * @var \TechDivision\ServletEngine\SessionFactory
     */
    protected $sessionFactory;

    /**
     * The session settings instance.
     *
     * @var \TechDivision\ServletEngine\SessionSettings
     */
    protected $sessionSettings;

    /**
     * The system user.
     *
     * @var string
     */
    protected $user;

    /**
     * The system group.
     *
     * @var string
     */
    protected $group;

    /**
     * The preferred umask.
     *
     * @var integer
     */
    protected $umask;

    /**
     * The flag that starts/stops the persistence manager.
     *
     * @var boolean
     */
    protected $run = false;

    /**
     * Initializes the session persistence manager.
     *
     * @return void
     */
    public function __construct()
    {
        $this->run = true;
    }

    /**
     * Injects the sessions.
     *
     * @param \TechDivision\Storage\StorageInterface $sessions The sessions
     *
     * @return void
     */
    public function injectSessions($sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * Injects the cecksums.
     *
     * @param \TechDivision\Storage\StorageInterface $checksums The checksums
     *
     * @return void
     */
    public function injectChecksums($checksums)
    {
        $this->checksums = $checksums;
    }

    /**
     * Injects the session settings.
     *
     * @param \TechDivision\ServletEngine\SessionSettings $sessionSettings Settings for the session handling
     *
     * @return void
     */
    public function injectSessionSettings($sessionSettings)
    {
        $this->sessionSettings = $sessionSettings;
    }

    /**
     * Injects the session marshaller.
     *
     * @param \TechDivision\ServletEngine\SessionMarshaller $sessionMarshaller The session marshaller instance
     *
     * @return void
     */
    public function injectSessionMarshaller($sessionMarshaller)
    {
        $this->sessionMarshaller = $sessionMarshaller;
    }

    /**
     * Injects the session factory.
     *
     * @param \TechDivision\ServletEngine\SessionFactory $sessionFactory The session factory instance
     *
     * @return void
     */
    public function injectSessionFactory($sessionFactory)
    {
        $this->sessionFactory = $sessionFactory;
    }

    /**
     * Injects the user.
     *
     * @param string $user The user
     *
     * @return void
     */
    public function injectUser($user)
    {
        $this->user = $user;
    }

    /**
     * Injects the group.
     *
     * @param string $group The group
     *
     * @return void
     */
    public function injectGroup($group)
    {
        $this->group = $group;
    }

    /**
     * Injects the umask.
     *
     * @param integer $umask The umask
     *
     * @return void
     */
    public function injectUmask($umask)
    {
        $this->umask = $umask;
    }

    /**
     * Returns the session checksum storage to watch changed sessions.
     *
     * @return \TechDivision\Storage\StorageInterface The session checksum storage
     */
    public function getChecksums()
    {
        return $this->checksums;
    }

    /**
     * Returns all sessions actually attached to the session manager.
     *
     * @return \TechDivision\Storage\StorageInterface The container with sessions
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * Returns the session settings.
     *
     * @return \TechDivision\ServletEngine\SessionSettings The session settings
     */
    public function getSessionSettings()
    {
        return $this->sessionSettings;
    }

    /**
     * Returns the session marshaller.
     *
     * @return \TechDivision\ServletEngine\SessionMarshaller The session marshaller
     */
    public function getSessionMarshaller()
    {
        return $this->sessionMarshaller;
    }

    /**
     * Returns the session factory.
     *
     * @return \TechDivision\ServletEngine\SessionFactory The session factory
     */
    public function getSessionFactory()
    {
        return $this->sessionFactory;
    }

    /**
     * Returns the system user.
     *
     * @return string The system user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns the system group.
     *
     * @return string The system user
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Returns the preferred umask.
     *
     * @return integer The preferred umask
     */
    public function getUmask()
    {
        return $this->umask;
    }

    /**
     * This is the main method that handles session persistence.
     *
     * @return void
     */
    public function run()
    {
        while ($this->run) {
            $this->persist();
            sleep(5);
        }
    }

    /**
     * Will set the owner and group on the passed directory.
     *
     * @return void
     */
    protected function prepareSessionDirectory()
    {

        // we don't do anything under Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return;
        }

        // set the umask that is necessary to create the directory
        $this->initUmask();

        // create the directory we want to store the sessions in
        $sessionSavePath = new \SplFileInfo($this->getSessionSettings()->getSessionSavePath());

        // we don't have a directory to change the user/group permissions for
        if ($sessionSavePath->isDir() === false) {

            // create the directory if necessary
            if (mkdir($sessionSavePath) === false) {
                throw new SessionDirectoryCreationException(sprintf('Directory %s to store sessions can\'t be created', $sessionSavePath));
            }
        }

        $this->setUserRights($sessionSavePath);
    }

    /**
     * Init the umask to use creating files/directories.
     *
     * @return void
     */
    protected function initUmask()
    {

        // don't do anything under Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return;
        }

        // set the configured umask to use
        umask($newUmask = $this->getUmask());

        // check if we have successfull set the umask
        if (umask() != $newUmask) { // check if set, throw an exception if not
            throw new \Exception("Can't set configured umask '$newUmask' found '" . umask() . "' instead");
        }
    }

    /**
     * Will set the owner and group on the passed directory.
     *
     * @param \SplFileInfo $targetDir The directory to set the rights for
     *
     * @return void
     */
    protected function setUserRights(\SplFileInfo $targetDir)
    {
        // we don't do anything under Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return;
        }

        // we don't have a directory to change the user/group permissions for
        if ($targetDir->isDir() === false) {
            return;
        }

        // As we might have several rootPaths we have to create several RecursiveDirectoryIterators.
        $directoryIterator = new \RecursiveDirectoryIterator(
            $targetDir,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        // We got them all, now append them onto a new RecursiveIteratorIterator and return it.
        $recursiveIterator = new \AppendIterator();
            // Append the directory iterator
            $recursiveIterator->append(
                new \RecursiveIteratorIterator(
                    $directoryIterator,
                    \RecursiveIteratorIterator::SELF_FIRST,
                    \RecursiveIteratorIterator::CATCH_GET_CHILD
                )
            );

        // Check for the existence of a user
        $user = $this->getUser();
        if (!empty($user)) {

            // Change the rights of everything within the defined dirs
            foreach ($recursiveIterator as $file) {
                chown($file, $user);
            }
        }

        // Check for the existence of a group
        $group = $this->getGroup();
        if (!empty($group)) {

            // Change the rights of everything within the defined dirs
            foreach ($recursiveIterator as $file) {
                chgrp($file, $group);
            }
        }
    }

    /**
     * This method will be invoked by the engine after the
     * servlet has been serviced.
     *
     * @return void
     */
    protected function persist()
    {

        // we want to know what inactivity timeout we've to check the sessions for
        $inactivityTimeout = $this->getSessionSettings()->getInactivityTimeout();

        // iterate over all the checksums (session that are active and loaded)
        foreach ($this->getSessions() as $id => $session) {

            // if we found a session
            if ($session instanceof ServletSession) {

                // if we don't have a checksum, this is a new session
                $checksum = null;
                if ($this->getChecksums()->has($id)) {
                    $checksum = $this->getChecksums()->get($id);
                }

                // load the sessions last activity timestamp
                $lastActivitySecondsAgo = time() - $session->getLastActivityTimestamp();

                // if the session doesn't change, and the last activity is < the inactivity timeout (1440 by default)
                if ($session->getId() != null && $checksum === $session->checksum() && $lastActivitySecondsAgo < $inactivityTimeout) {
                    continue;
                }

                // we want to detach the session (to free memory), when the last activity is > the inactivity timeout (1440 by default)
                if ($session->getId() != null && $checksum === $session->checksum() && $lastActivitySecondsAgo > $inactivityTimeout) {
                    // prepare the session filename
                    $sessionFilename = $this->getSessionSavePath($this->getSessionSettings()->getSessionFilePrefix() . $id);
                    // update the checksum and the file that stores the session data
                    file_put_contents($sessionFilename, $this->marshall($session));
                    $this->getChecksums()->remove($id);
                    $this->getSessions()->remove($id);
                    continue;
                }

                // we want to persist the session because its data has been changed
                if ($session->getId() != null && $checksum !== $session->checksum()) {
                    // prepare the session filename
                    $sessionFilename = $this->getSessionSavePath($this->getSessionSettings()->getSessionFilePrefix() . $id);
                    // update the checksum and the file that stores the session data
                    file_put_contents($sessionFilename, $this->marshall($session));
                    $this->getChecksums()->set($id, $session->checksum());
                    continue;
                }

                // we want to remove the session file, because the session has been destroyed
                if ($session->getId() == null && $checksum !== $session->checksum()) {
                    // prepare the session filename
                    $sessionFilename = $this->getSessionSavePath($this->getSessionSettings()->getSessionFilePrefix() . $id);
                    // delete the file containing the session data if available
                    $this->removeSessionFile($sessionFilename);
                    $this->getChecksums()->remove($id);
                    $this->getSessions()->remove($id);
                }
            }
        }
    }

    /**
     * Returns the default path to persist sessions.
     *
     * @param string $toAppend A relative path to append to the session save path
     *
     * @return string The default path to persist session
     */
    public function getSessionSavePath($toAppend = null)
    {
        // load the default path
        $sessionSavePath = $this->getSessionSettings()->getSessionSavePath();

        // check if we've something to append
        if ($toAppend != null) {
            $sessionSavePath = $sessionSavePath . DIRECTORY_SEPARATOR . $toAppend;
        }

        // return the session save path
        return $sessionSavePath;
    }

    /**
     * Initializes the session manager instance and unpersists the all sessions that has
     * been used during the time defined with the last inactivity timeout defined in the
     * session configuration.
     *
     * If the session data could not be loaded, because the files data is corrupt, the
     * file with the session data will be deleted.
     *
     * @return void
     */
    public function initialize()
    {

        // prepare the directory to store the sessions in
        $this->prepareSessionDirectory();

        // prepare the glob to load the session
        $glob = $this->getSessionSavePath($this->getSessionSettings()->getSessionFilePrefix() . '*');

        // we want to filter the session we initialize on server start
        $sessionFilter = new SessionFilter(new \GlobIterator($glob), $this->getSessionSettings()->getInactivityTimeout());

        // iterate through all session files and initialize them
        foreach ($sessionFilter as $sessionFile) {

            try {

                // unpersist the session data itself
                $this->loadSessionFromFile($sessionFile->getPathname());

            } catch (SessionDataNotReadableException $sdnre) {

                // this maybe happens when the session file is corrupt
                $this->removeSessionFile($pathname);
            }
        }
    }

    /**
     * Unpersists the session with the passed ID from the persistence layer and
     * reattaches it to the internal session storage.
     *
     * @param string $id The ID of the session we want to unpersist
     *
     * @return void
     */
    protected function unpersist($id)
    {

        try {

            // try to load the session with the passed ID
            if ($this->getSessions()->has($id) === false) {

                // prepare the pathname to the file containing the session data
                $filename = $this->getSessionSettings()->getSessionFilePrefix() . $id;
                $pathname = $this->getSessionSavePath($filename);

                // unpersist the session data itself
                $this->loadSessionFromFile($pathname);
            }

        } catch (SessionDataNotReadableException $sdnre) {

            // this maybe happens when the session file is corrupt
            $this->removeSessionFile($pathname);
        }
    }

    /**
     * Checks if a file with the passed name containing session data exists.
     *
     * @param string $pathname The path of the file to check
     *
     * @return boolean TRUE if the file exists, else FALSE
     */
    public function sessionFileExists($pathname)
    {
        return file_exists($pathname);
    }

    /**
     * Removes the session file with the passed name containing session data.
     *
     * @param string $pathname The path of the file to remove
     *
     * @return boolean TRUE if the file has successfully been removed, else FALSE
     */
    public function removeSessionFile($pathname)
    {
        if (file_exists($pathname)) {
            return unlink($pathname);
        }
        return false;
    }

    /**
     * Tries to load the session data from the passed filename.
     *
     * @param string $pathname The path of the file to load the session data from
     *
     * @return void
     * @throws \TechDivision\ServletEngine\SessionDataNotReadableException Is thrown if the file containing the session data is not readable
     */
    public function loadSessionFromFile($pathname)
    {

        // the requested session file is not a valid file
        if ($this->sessionFileExists($pathname) === false) {
            return;
        }

        // decode the session from the filesystem
        if (($marshalled = file_get_contents($pathname)) === false) {
            throw new SessionDataNotReadableException(sprintf('Can\'t load session data from file %s', $pathname));
        }

        // create a new session instance from the marshalled object representation
        $session = $this->unmarshall($marshalled);

        // load session ID and checksum
        $id = $session->getId();
        $checksum = $session->checksum();

        // add the sessions checksum
        $this->getChecksums()->set($id, $checksum);

        // add the session to the sessions
        $this->getSessions()->set($id, $session);
    }

    /**
     * Initializes the session instance from the passed JSON string. If the encoded
     * data contains objects, they will be unserialized before reattached to the
     * session instance.
     *
     * @param string $marshalled The marshalled session representation
     *
     * @return \TechDivision\Servlet\ServletSession The unmarshalled servlet session instance
     */
    public function unmarshall($marshalled)
    {

        // create a new and empty servlet session instance
        $servletSession = $this->getSessionFactory()->nextFromPool();

        // unmarshall the session data
        $this->getSessionMarshaller()->unmarshall($servletSession, $marshalled);

        // returns the initialized servlet session instance
        return $servletSession;
    }

    /**
     * Transforms the passed session instance into a JSON encoded string. If the data contains
     * objects, each of them will be serialized before store them to the persistence layer.
     *
     * @param \TechDivision\Servlet\ServletSession $servletSession The servlet session to be transformed
     *
     * @return string The marshalled servlet session representation
     */
    public function marshall(ServletSession $servletSession)
    {
        return $this->getSessionMarshaller()->marshall($servletSession);
    }

    /**
     * Stops the peristence manager.
     *
     * @return void
     */
    public function stop()
    {
        $this->run = false;
    }
}