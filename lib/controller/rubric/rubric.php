<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\Rubric;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Config\Config;

class Rubric extends Base
{

    public function getPage(Request $request, Response $response, array $args): Response
    {
        // prepare
        $this->params = [
            'menu_code'  => "rubrics",
            'blocks'  => $request->getQueryParam('blocks'),
            'limit'  => $request->getQueryParam('limit'),
        ];

        // if ($this->params['blocks'] === NULL) {
        //     $this->params['blocks'] = 'news,articles,events';
        // }
        if ($this->params['limit'] === NULL) {
            $this->params['limit'] = '50';
        }
        
        // repository
        $blocks = $this->getRepository()->getRubricIndexPage((int)$args['id'], (array)$this->params, $this->getUserId($request));

        return $this->response($response, self::RESPONSE_SUCCESS, $blocks, StatusCode::HTTP_OK);
    }

    public function getList(Request $request, Response $response, array $args): Response
    {
        $this->params = [ 
            'menu_code'  => "rubrics", 
            'order'   => $request->getQueryParam('order'),
            'limit'   => $request->getQueryParam('limit'), 
        ];
        
        // repository
        $list = $this->getRepository()->getRubricsList($this->params); 

        return $this->response($response, self::RESPONSE_SUCCESS, $list, StatusCode::HTTP_OK);
    }
}
