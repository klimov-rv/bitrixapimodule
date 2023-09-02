<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\Navigation;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;

use Sotbit\RestAPI\Service\Navigation;
use Sotbit\RestAPI\Exception\UserException;
use Sotbit\RestAPI\Core;
use Sotbit\RestAPI\Localisation as l;
use Sotbit\RestAPI\Controller\BaseController;
use Sotbit\RestAPI\Repository\NavigationRepository;

/**
 * Class Base
 *
 * @package Sotbit\RestAPI\Controller\Navigation
 * @author Andrey Sapronov <a.sapronov@sotbit.ru>
 * Date: 20.10.2022
 */
abstract class Base extends BaseController
{
    /**
     * Navigation find service
     *
     * @return \Sotbit\RestAPI\Repository\NavigationRepository
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function getRepository(): NavigationRepository
    {
        return $this->container->get('navigation_repository');
    }

}
