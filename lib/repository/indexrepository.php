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
    
    public function getMenuList(array $params, int $userId)
    {
        // check permission
        // $this->permission->user($userId)->Blocks($iblockId, $blocksId);

        $menulist = new _Index\Menu();

        return $menulist->setUserId($userId)->get($params);
    }
    
    // /**
    //  * https://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getlist.php
    //  *
    //  * @param  array  $params
    //  *
    //  * @return array
    //  * @throws IndexException
    //  */
    // public function getSectionsList(array $params)
    // {
    //     $userId = $params['user_id'];

    //     // prepare params to Index params
    //     $params = $this->prepareNavigationIndex($params);

    //     //$params['filter']['IBLOCK_ID'] = $params;


    //     // check permission
    //     $this->permission->user($userId)->section((int) $params['filter']['IBLOCK_ID'], 0);

    //     $section = new _Index\Section();

    //     return $section->setUserId($userId)->list($params);
    // }

    protected function prepareReturn(array $array, string $typeView = self::TYPE_LIST): array
    {
        $sizePreview = $this->getSizeImage($typeView);
        if($array) {
            // prepare picture
            if(is_numeric($array['PICTURE'])) {
                $array['PICTURE'] = $this->getPictureSrc((int) $array['PICTURE'], $sizePreview);
            }

            // prepare detail picture
            if(is_numeric($array['DETAIL_PICTURE'])) {
                $array['DETAIL_PICTURE'] = $this->getPictureSrc((int) $array['DETAIL_PICTURE'], $sizePreview);
            } else if (isset($array['DETAIL_PICTURE'])) {
                $array['DETAIL_PICTURE']['ORIGINAL'] = $array['DETAIL_PICTURE']['RESIZE'] = self::IMAGE_NOT_FOUND;
            }

            // prepare preview picture
            if(is_numeric($array['PREVIEW_PICTURE'])) {
                $array['PREVIEW_PICTURE'] = $this->getPictureSrc((int) $array['PREVIEW_PICTURE'], $sizePreview);
            } else if (isset($array['PREVIEW_PICTURE'])) {
                $array['PREVIEW_PICTURE']['ORIGINAL'] = $array['PREVIEW_PICTURE']['RESIZE'] = self::IMAGE_NOT_FOUND;
            }
        }

        return $array;
    }
}
