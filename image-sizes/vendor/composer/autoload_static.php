<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit92775625bacd7a7149e1c6026061ae02
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WebPConvert\\' => 12,
        ),
        'I' => 
        array (
            'ImageMimeTypeGuesser\\' => 21,
        ),
        'E' => 
        array (
            'ExecWithFallback\\' => 17,
        ),
        'C' => 
        array (
            'Codexpert\\ThumbPress\\App\\' => 25,
            'Codexpert\\ThumbPress\\API\\' => 25,
            'Codexpert\\ThumbPress\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WebPConvert\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/webp-convert/src',
        ),
        'ImageMimeTypeGuesser\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/image-mime-type-guesser/src',
        ),
        'ExecWithFallback\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/exec-with-fallback/src',
        ),
        'Codexpert\\ThumbPress\\App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app',
        ),
        'Codexpert\\ThumbPress\\API\\' => 
        array (
            0 => __DIR__ . '/../..' . '/api',
        ),
        'Codexpert\\ThumbPress\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $classMap = array (
        'Codexpert\\Plugin\\Base' => __DIR__ . '/..' . '/codexpert/plugin/src/Base.php',
        'Codexpert\\Plugin\\Fields' => __DIR__ . '/..' . '/codexpert/plugin/src/Fields.php',
        'Codexpert\\Plugin\\Metabox' => __DIR__ . '/..' . '/codexpert/plugin/src/Metabox.php',
        'Codexpert\\Plugin\\Notice' => __DIR__ . '/..' . '/codexpert/plugin/src/Notice.php',
        'Codexpert\\Plugin\\Settings' => __DIR__ . '/..' . '/codexpert/plugin/src/Settings.php',
        'Codexpert\\Plugin\\Setup' => __DIR__ . '/..' . '/codexpert/plugin/src/Setup.php',
        'Codexpert\\Plugin\\Table' => __DIR__ . '/..' . '/codexpert/plugin/src/Table.php',
        'Codexpert\\Plugin\\Widget' => __DIR__ . '/..' . '/codexpert/plugin/src/Widget.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Pluggable\\Marketing\\Deactivator' => __DIR__ . '/..' . '/pluggable/marketing/src/Deactivator.php',
        'Pluggable\\Marketing\\Feature' => __DIR__ . '/..' . '/pluggable/marketing/src/Feature.php',
        'Pluggable\\Marketing\\Survey' => __DIR__ . '/..' . '/pluggable/marketing/src/Survey.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit92775625bacd7a7149e1c6026061ae02::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit92775625bacd7a7149e1c6026061ae02::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit92775625bacd7a7149e1c6026061ae02::$classMap;

        }, null, ClassLoader::class);
    }
}
