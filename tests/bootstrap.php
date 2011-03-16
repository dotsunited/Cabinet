<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Setup PHPUnit autoloading from include_path
spl_autoload_register(function($className) {
    if (strpos($className, 'PHPUnit_') === 0) {
        require str_replace('_', '/', $className) . '.php';
    }
});

// Setup Cabinet test asset autoloading
spl_autoload_register(function($className) {
    if (strpos($className, 'DotsUnited\\Cabinet\\') === 0) {
        $file = __DIR__ . '/' . str_replace('\\', '/', $className) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
});

if (file_exists($file = __DIR__.'/../autoload.php')) {
    require_once $file;
} elseif (file_exists($file = __DIR__.'/../autoload.php.dist')) {
    require_once $file;
}
