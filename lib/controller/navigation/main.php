<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\Navigation;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Config\Config;

class Main extends Base
{
    public function getMainMenu(Request $request, Response $response, array $args): Response
    {
        $this->params = [ 
            'menu_code'  => "rubrics",
            // 'params' - Array("CODE"=>"TO_MAIN_MENU"
            // 'params' - Array("CODE"=>"THEME_ICON"
            'order'   => $request->getQueryParam('order'),
            'limit'   => $request->getQueryParam('limit'), 
        ];  
        // TODO проверить работу по умолчанию и с указанием ордер
        $this->params['order'] = (array)($params['order'] ?? ['ID' => 'ASC']); 


        // repository
        $list = $this->getRepository()->getMenu($this->params); 

        return $this->response($response, self::RESPONSE_SUCCESS, $list, StatusCode::HTTP_OK);
    }

}
