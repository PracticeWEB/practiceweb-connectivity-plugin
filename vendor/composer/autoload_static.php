<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7335bad536371cbf8a69ed66c032a764
{
    public static $files = array (
        'a5f882d89ab791a139cd2d37e50cdd80' => __DIR__ . '/..' . '/tgmpa/tgm-plugin-activation/class-tgm-plugin-activation.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Sift\\Practiceweb\\Connectivity\\' => 30,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Sift\\Practiceweb\\Connectivity\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7335bad536371cbf8a69ed66c032a764::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7335bad536371cbf8a69ed66c032a764::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
