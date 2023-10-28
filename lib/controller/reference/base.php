<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\Reference;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;

use Sotbit\RestAPI\Service\Reference;
use Sotbit\RestAPI\Exception\UserException;
use Sotbit\RestAPI\Core;
use Sotbit\RestAPI\Localisation as l;
use Sotbit\RestAPI\Controller\BaseController;
use Sotbit\RestAPI\Repository\ReferenceRepository;

/**
 * Class Base
 *
 * @package Sotbit\RestAPI\Controller\Reference
 * @author Andrey Sapronov <a.sapronov@sotbit.ru>
 * Date: 20.10.2022
 */
abstract class Base extends BaseController
{
    /**
     * Reference find service
     *
     * @return \Sotbit\RestAPI\Repository\ReferenceRepository
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function getRepository(): ReferenceRepository
    {
        return $this->container->get('reference_repository');
    }

}
