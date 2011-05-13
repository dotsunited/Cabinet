<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet\Adapter;

/**
 * DotsUnited\Cabinet\AdapterInterface
 *
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 */
interface AdapterInterface
{
    /**
     * Constructor.
     *
     * @param array $config
     */
    function __construct(array $config = array());

    /**
     * Import a external local file.
     *
     * @param string $external The external local file
     * @param string $file The name to store the file under
     * @return boolean
     */
    function import($external, $file);

    /**
     * Write data to a file.
     *
     * @param string $file
     * @param string|array|resource $data
     * @return boolean
     */
    function write($file, $data);

    /**
     * Read data from a file.
     *
     * @param string $file
     * @return string|boolean The contents or false on failure
     */
    function read($file);

    /**
     * Return a read-only stream resource for a file.
     *
     * @param string $file
     * @return resource|boolean The resource or false on failure
     */
    function stream($file);

    /**
     * Copy a file internally.
     *
     * @param string $src
     * @param string $dest
     * @return boolean Whether the file was copied
     */
    function copy($src, $dest);

    /**
     * Rename a file internally.
     *
     * @param string $src
     * @param string $dest
     * @return boolean Whether the file was renamed
     */
    function rename($src, $dest);

    /**
     * Delete a file.
     *
     * @param string $file
     * @return boolean Whether the file was deleted
     */
    function unlink($file);

    /**
     * Return whether a file exists.
     *
     * @param string $file
     * @return boolean Whether the file exists
     */
    function exists($file);

    /**
     * Return the files size.
     *
     * @param string $file
     * @return integer The file size in bytes
     */
    function size($file);

    /**
     * Try to determine and return a files MIME content type.
     *
     * @param string $file
     * @return string The MIME content type
     */
    function type($file);

    /**
     * Return the web-accessible uri for the given file.
     *
     * @param string $file
     * @return string The file uri
     */
    function uri($file);
}
