<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb4255b8a285653cf8e753b33b0fc9071
{
    public static $prefixLengthsPsr4 = array (
        'a' => 
        array (
            'andreskrey\\Readability\\' => 23,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'andreskrey\\Readability\\' => 
        array (
            0 => __DIR__ . '/..' . '/andreskrey/readability.php/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb4255b8a285653cf8e753b33b0fc9071::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb4255b8a285653cf8e753b33b0fc9071::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
