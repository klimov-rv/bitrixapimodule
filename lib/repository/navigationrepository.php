<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository;

use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Repository\Navigation as _Navigation,
    Sotbit\RestAPI\Exception\NavigationException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l;

class NavigationRepository extends BaseRepository
{

    /**
     * OrderRepository constructor.
     *
     * @throws \Bitrix\Main\LoaderException
     */
    public function __construct()
    {
        parent::__construct();
        if(!Loader::includeModule("iblock")) {
            throw new NavigationException(l::get('ERROR_MODULE_IBLOCK'), StatusCode::HTTP_BAD_REQUEST);
        }
    }
 

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    } 
    
     
    public function getMenu(array $params): array
    {
        // check permission
        // $this->permission->user($userId)->Blocks($iblockId, $blocksId);

        $navigation = new _Navigation\Menu();

        return $navigation->setUserId($userId)->get($params);
    } 

}
