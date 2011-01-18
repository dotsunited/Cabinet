<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet\Filter;

/**
 * DotsUnited\Cabinet\Filter\HashedSubpath
 *
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 */
class HashedSubpath implements FilterInterface
{
    /**
     * The level.
     *
     * @var integer
     */
    protected $level = 0;

    /**
     * The callback.
     *
     * @var callable
     */
    protected $callback = 'md5';

    /**
     * Whether to preserve dirs.
     *
     * @var boolean
     */
    protected $preserveDirs = false;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (isset($config['level'])) {
            $this->setLevel($config['level']);
        }

        if (isset($config['callback'])) {
            $this->setCallback($config['callback']);
        }

        if (isset($config['preserve_dirs'])) {
            $this->setPreserveDirs($config['preserve_dirs']);
        }
    }

    /**
     * Set level.
     *
     * @param integer $level
     * @return HashedSubpath
     */
    public function setLevel($level)
    {
        $this->level = (integer) $level;
        return $this;
    }

    /**
     * Get level.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set callback.
     *
     * @param callback $callback
     * @return HashedSubpath
     * @throws \InvalidArgumentException
     */
    public function setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback');
        }

        $this->callback = $callback;
        return $this;
    }

    /**
     * Get callback.
     *
     * @return callback
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Set preserve dirs.
     *
     * @param boolean $preserveDirs
     * @return HashedSubpath
     */
    public function setPreserveDirs($preserveDirs)
    {
        $this->preserveDirs = (boolean) $preserveDirs;
        return $this;
    }

    /**
     * Get preserve dirs.
     *
     * @return boolean
     */
    public function getPreserveDirs()
    {
        return $this->preserveDirs;
    }

    /**
     * Returns the result of filtering $value.
     *
     * @param mixed $value
     * @return mixed
     * @throws \RuntimeException
     */
    public function filter($value)
    {
        $level = $this->getLevel();

        if ($level <= 0) {
            return $value;
        }

        $callback     = $this->getCallback();
        $preserveDirs = $this->getPreserveDirs();

        $dirName      = dirname($value);
        $baseName     = basename($value);

        if ($preserveDirs) {
            $toHash = $baseName;
        } else {
            $toHash = $value;
        }

        if (is_string($callback) && in_array($callback, hash_algos())) {
            $hash = hash($callback, $toHash);
        } else {
            $hash = call_user_func($callback, $toHash);
        }

        $hash = trim($hash);

        if ($hash === '') {
            throw new \RuntimeException('Invalid callback (empty hash returned)');
        }

        $hash  = substr($hash, 0, $level);
        $parts = preg_split('//u', $hash, -1, PREG_SPLIT_NO_EMPTY);

        if ($preserveDirs && !empty($dirName) && $dirName != '.') {
            array_unshift($parts, $dirName);
        }

        return implode('/', $parts). '/' . $baseName;
    }
}
