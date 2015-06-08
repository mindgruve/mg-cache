<?php

defined('ABSPATH') or die();

class CacheProviderFactory
{
    public static function build($key)
    {
        switch (strtolower($key)) {
            case 'memory':
                return new \Doctrine\Common\Cache\ArrayCache();
                break;
            case 'apc':
                return new \Doctrine\Common\Cache\ApcCache();
                break;
            case 'filesystem':
                return new \Doctrine\Common\Cache\FilesystemCache(
                    MgCacheHelper::$cacheDir,
                    MgCacheHelper::$fileExtension
                );
                break;
            case 'memcache':
                return new \Doctrine\Common\Cache\MemcacheCache();
                break;
            case 'memcached':
                return new \Doctrine\Common\Cache\MemcachedCache();
                break;
            case 'phpfile':
                return new \Doctrine\Common\Cache\PhpFileCache(MgCacheHelper::$cacheDir, MgCacheHelper::$fileExtension);
                break;
            case 'redis':
                return new \Doctrine\Common\Cache\RedisCache();
                break;
            case 'wincache':
                return new \Doctrine\Common\Cache\WinCacheCache();
                break;
            case 'xcache':
                return new \Doctrine\Common\Cache\XcacheCache();
                break;
            case 'zend':
                return new \Doctrine\Common\Cache\ZendDataCache();
                break;
            default:
                return new \Doctrine\Common\Cache\FilesystemCache(
                    MgCacheHelper::$cacheDir,
                    MgCacheHelper::$fileExtension
                );
                break;
        }
    }
}
