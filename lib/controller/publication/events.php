<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\Publication;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Config\Config;

class Events extends Base
{
 
    public function getOne(Request $request, Response $response, array $args): Response
    { 

        $args['iblock_id'] = "events";
        // repository
        $product = $this->getRepository()->getPublicationTypeEl((string)$args['iblock_id'], (int)$args['id'], $this->getUserId($request)); 

        return $this->response($response, self::RESPONSE_SUCCESS, $product, StatusCode::HTTP_OK);
    }

    public function getList(Request $request, Response $response, array $args): Response
    {
 
        // prepare
        $this->params = [
            'user_id' => $this->getUserId($request),
            'publication_type' => "events",
            'select'  => $request->getQueryParam('select'),
            'filter'  => $request->getQueryParam('filter'),
            'order'   => $request->getQueryParam('order'),
            'limit'   => $request->getQueryParam('limit'),
            'page'    => $request->getQueryParam('page'),
        ];

        // repository
        $catalog = $this->getRepository()->getPublicationTypeList($this->params);

        return $this->response($response, self::RESPONSE_SUCCESS, $catalog, StatusCode::HTTP_OK);
    }
 
}
