<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet\TestAsset;

use DotsUnited\Cabinet\Adapter\AdapterInterface;

/**
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 */
class TestAdapter implements AdapterInterface
{
    /**
     * @var array
     */
    public $config;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->config = $config;
    }

    /**
     * Import a external local file.
     *
     * @param  string  $external The external local file
     * @param  string  $file     The name to store the file under
     * @return boolean
     */
    public function import($external, $file) {}

    /**
     * Write data to a file.
     *
     * @param  string                $file
     * @param  string|array|resource $data
     * @return boolean
     */
    public function write($file, $data) {}

    /**
     * Read data from a file.
     *
     * @param  string         $file
     * @return string|boolean The contents or false on failure
     */
    public function read($file) {}

    /**
     * Return a read-only stream resource for a file.
     *
     * @param  string           $file
     * @return resource|boolean The resource or false on failure
     */
    public function stream($file) {}

    /**
     * Copy a file internally.
     *
     * @param  string  $src
     * @param  string  $dest
     * @return boolean Whether the file was copied
     */
    public function copy($src, $dest) {}

    /**
     * Rename a file internally.
     *
     * @param  string  $src
     * @param  string  $dest
     * @return boolean Whether the file was renamed
     */
    public function rename($src, $dest) {}

    /**
     * Delete a file.
     *
     * @param  string  $file
     * @return boolean Whether the file was deleted
     */
    public function unlink($file) {}

    /**
     * Return whether a file exists.
     *
     * @param  string  $file
     * @return boolean Whether the file exists
     */
    public function exists($file) {}

    /**
     * Return the files size.
     *
     * @param  string  $file
     * @return integer The file size in bytes
     */
    public function size($file) {}

    /**
     * Try to determine and return a files MIME content type.
     *
     * @param  string $file
     * @return string The MIME content type
     */
    public function type($file) {}

    /**
     * Return the uri for the given file.
     *
     * @param  string $file
     * @return string The file uri
     */
    public function uri($file) {}
}
