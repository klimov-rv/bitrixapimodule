<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\Publication;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;

use Sotbit\RestAPI\Service\Publication;
use Sotbit\RestAPI\Exception\UserException;
use Sotbit\RestAPI\Core;
use Sotbit\RestAPI\Localisation as l;
use Sotbit\RestAPI\Controller\BaseController;
use Sotbit\RestAPI\Repository\PublicationRepository;

/**
 * Class Base
 *
 * @package Sotbit\RestAPI\Controller\Publication
 * @author Andrey Sapronov <a.sapronov@sotbit.ru>
 * Date: 20.10.2022
 */
abstract class Base extends BaseController
{
    /**
     * Publication find service
     *
     * @return \Sotbit\RestAPI\Repository\PublicationRepository
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function getRepository(): PublicationRepository
    {
        return $this->container->get('publication_repository');
    }

}
