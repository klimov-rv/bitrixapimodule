<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\Index;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Config\Config;

class Blocks extends Base
{

    public function get(Request $request, Response $response, array $args): Response
    {
        // prepare
        $this->params = [
            'blocks'  => $request->getQueryParam('blocks'),
            'limit'  => $request->getQueryParam('limit'),
        ];

        if ($this->params['blocks'] === NULL) {
            $this->params['blocks'] = 'news,articles,events';
        }
        if ($this->params['limit'] === NULL) {
            $this->params['limit'] = '3';
        }
 
        // repository
        $blocks = $this->getRepository()->getBlocks((array)$this->params , $this->getUserId($request));

        return $this->response($response, self::RESPONSE_SUCCESS, $blocks, StatusCode::HTTP_OK);
    }

    public function getList(Request $request, Response $response, array $args): Response
    {
        // prepare
        $this->params = [
            'user_id' => $this->getUserId($request),
            'select'  => $request->getQueryParam('select'),
            'filter'  => $request->getQueryParam('filter'),
            'order'   => $request->getQueryParam('order'),
            'page'    => $request->getQueryParam('page'),
            'limit'   => $request->getQueryParam('limit'),
        ];

        $this->params['filter']['IBLOCK_ID'] = (int) $args['iblock_id'];

        // event detail
        //$this->getEventService()->dispatch(new OrderEvent($args), OrderEvent::DETAIL_BEFORE);

        // repository
        $list = $this->getRepository()->getSectionsList($this->params);

        // event detail
        //$this->getEventService()->dispatch(new OrderEvent($order), OrderEvent::DETAIL_AFTER);

        return $this->response($response, self::RESPONSE_SUCCESS, $list, StatusCode::HTTP_OK);
    }

}
