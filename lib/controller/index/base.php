<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\Index;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;

use Sotbit\RestAPI\Service\Index;
use Sotbit\RestAPI\Exception\UserException;
use Sotbit\RestAPI\Core;
use Sotbit\RestAPI\Localisation as l;
use Sotbit\RestAPI\Controller\BaseController;
use Sotbit\RestAPI\Repository\IndexRepository;

/**
 * Class Base
 *
 * @package Sotbit\RestAPI\Controller\Index
 * @author Andrey Sapronov <a.sapronov@sotbit.ru>
 * Date: 20.10.2022
 */
abstract class Base extends BaseController
{
    /**
     * Index find service
     *
     * @return \Sotbit\RestAPI\Repository\IndexRepository
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function getRepository(): IndexRepository
    {
        return $this->container->get('index_repository');
    }

}
