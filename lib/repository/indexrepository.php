<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository;

use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Repository\Index as _Index,
    Sotbit\RestAPI\Repository\Catalog\Product,
    Bitrix\Catalog\ProductTable,
    Sotbit\RestAPI\Exception\IndexException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l;

class IndexRepository extends BaseRepository
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
            throw new IndexException(l::get('ERROR_MODULE_IBLOCK'), StatusCode::HTTP_BAD_REQUEST);
        }
    }
 

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }


    /**
     * @param  mixed  $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserGroups()
    {
        $groups = $this->user->get($this->getUserId())['groups'];
        if(is_array($groups)) {
            return $groups;
        }

        return [];
    }
    
    public function getBlocks(array $params, int $userId)
    {
        // check permission
        // $this->permission->user($userId)->Blocks($iblockId, $blocksId);

        $blocks = new _Index\Blocks();

        return $blocks->setUserId($userId)->get($params);
    } 
}
