<?php

namespace Database\DataAccess;

use Database\DataAccess\Implementations\ComputerPartDAOImpl;
use Database\DataAccess\Implementations\ComputerPartDAOMemcachedImpl;
use Database\DataAccess\Implementations\UserDAOImpl;
use Database\DataAccess\Interfaces\ComputerPartDAO;
use Database\DataAccess\Interfaces\UserDAO;
use Helpers\Settings;

class DAOFactory
{
    public static function getComputerPartDAO(): ComputerPartDAO{
        $driver = Settings::env('DATABASE_DRIVER');

        return match ($driver) {
            'memcached' => new ComputerPartDAOMemcachedImpl(),
            default => new ComputerPartDAOImpl(),
        };
    }
    public static function getUserDAO(): UserDAO{
        $driver = Settings::env('DATABASE_DRIVER');

        return match ($driver) {
            // 'memcached' => new UserDaoMe(),
            default => new UserDAOImpl(),
        };
    }
}