<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet;

/**
 * DotsUnited\Cabinet\Cabinet
 *
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 */
class Cabinet
{
    /**
     * Factory for DotsUnited\Cabinet\Adapter classes.
     *
     * First argument may be a string containing the adapter class
     * name, e.g. 'DotsUnited\Cabinet\Adapter\Stream'.
     * If it does not contain a namespace separator (\), it is assumed to be
     * the base of an adapter class name, e.g. 'Stream' corresponds to class
     * DotsUnited\Cabinet\Adapter\Stream.
     *
     * First argument may alternatively be an array. The adapter class name
     * is read from the 'adapter' key. The adapter config parameters are read
     * from the 'config' key.
     *
     * Second argument is optional and may be an associative array of key-value
     * pairs. This is used as the argument to the adapter constructor.
     *
     * If the first argument is of type array, it is assumed to contain
     * all parameters, and the second argument is ignored.
     *
     * @param string|array $adapter string Name of (base) adapter class, or array.
     * @param array $config OPTIONAL; An array of adapter configurations.
     * @return \DotsUnited\Cabinet\Adapter\AdapterInterface
     * @throws \InvalidArgumentException
     */
    public static function factory($adapter, array $config = array())
    {
        if (is_array($adapter)) {
            if (isset($adapter['config'])) {
                $config = $adapter['config'];
            }

            if (isset($adapter['adapter'])) {
                $adapter = (string) $adapter['adapter'];
            } else {
                $adapter = null;
            }
        }

        if (!is_string($adapter) || empty($adapter)) {
            throw new \InvalidArgumentException('Adapter name must be specified in a string');
        }

        if (false === strpos($adapter, '\\')) {
            $adapter = 'DotsUnited\\Cabinet\\Adapter\\' . $adapter;
        }

        $instance = new $adapter($config);

        if (!$instance instanceof Adapter\AdapterInterface) {
            throw new \InvalidArgumentException('Adapter class "' . $adapter . '" does not implement DotsUnited\Cabinet\Adapter\AdapterInterface');
        }

        return $instance;
    }
}
