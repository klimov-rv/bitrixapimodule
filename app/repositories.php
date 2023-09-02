<?php

declare(strict_types=1);

use Sotbit\RestAPI\Repository\UserRepository; 
use Sotbit\RestAPI\Repository\IndexRepository; 
use Sotbit\RestAPI\Repository\NavigationRepository; 
use Sotbit\RestAPI\Repository\PublicationRepository; 
use Sotbit\RestAPI\Repository\RubricRepository; 

use Psr\Container\ContainerInterface;
use Sotbit\RestAPI\Core\Helper;

$container['user_repository'] = static function(
    ContainerInterface $container
): UserRepository {
    return new UserRepository();
}; 

$container['index_repository'] = static function(
    ContainerInterface $container
): IndexRepository {
    return new IndexRepository();
}; 

$container['navigation_repository'] = static function(
    ContainerInterface $container
): NavigationRepository {
    return new NavigationRepository();
}; 

$container['publication_repository'] = static function(
    ContainerInterface $container
): PublicationRepository {
    return new PublicationRepository();
};

$container['rubric_repository'] = static function(
    ContainerInterface $container
): RubricRepository {
    return new RubricRepository();
};

/**
 * Include custom repository from file
 */
if(Helper::checkCustomFile(basename(__FILE__))) {
    require Helper::checkCustomFile(basename(__FILE__));
}