<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\Reference;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Config\Config;

class Reference extends Base
{ 
 
    public function getList(Request $request, Response $response, array $args): Response
    {
    
        // prepare
        // $this->params = [ 
        //     'select'  => $request->getQueryParam('select'),
        //     'filter'  => $request->getQueryParam('filter'),
        //     'order'   => $request->getQueryParam('order'),
        //     'limit'   => $request->getQueryParam('limit'),
        //     'page'    => $request->getQueryParam('page'),
        // ];

        // repository
        $catalog = $this->getRepository()->getReferencesTypeList($this->params);

        return $this->response($response, self::RESPONSE_SUCCESS, $catalog, StatusCode::HTTP_OK);
    } 
    
    public function getCourts(Request $request, Response $response, array $args): Response
    {
    
        // prepare
        $this->params = [
            'user_id' => $this->getUserId($request),
            'reference_type' => "courts",
            'select'  => $request->getQueryParam('select'),
            'filter'  => $request->getQueryParam('filter'),
            'order'   => $request->getQueryParam('order'),
            'limit'   => $request->getQueryParam('limit'),
            'page'    => $request->getQueryParam('page'),
        ];

        // repository
        $catalog = $this->getRepository()->getCourtsList($this->params);

        return $this->response($response, self::RESPONSE_SUCCESS, $catalog, StatusCode::HTTP_OK);
    } 
}
