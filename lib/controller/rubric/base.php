<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\Rubric;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;

use Sotbit\RestAPI\Service\Rubric;
use Sotbit\RestAPI\Exception\UserException;
use Sotbit\RestAPI\Core;
use Sotbit\RestAPI\Localisation as l;
use Sotbit\RestAPI\Controller\BaseController;
use Sotbit\RestAPI\Repository\RubricRepository;

/**
 * Class Base
 *
 * @package Sotbit\RestAPI\Controller\Rubric
 * @author Andrey Sapronov <a.sapronov@sotbit.ru>
 * Date: 20.10.2022
 */
abstract class Base extends BaseController
{
    /**
     * Rubric find service
     *
     * @return \Sotbit\RestAPI\Repository\RubricRepository
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function getRepository(): RubricRepository
    {
        return $this->container->get('rubric_repository');
    }

}
