<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository;

use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Repository\Rubric as _Rubric,
    Sotbit\RestAPI\Exception\RubricException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l;

class RubricRepository extends BaseRepository
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
            throw new RubricException(l::get('ERROR_MODULE_IBLOCK'), StatusCode::HTTP_BAD_REQUEST);
        }
    }
 

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    } 
    
     
    public function getRubricsList(array $params): array
    {
        // check permission
        // $this->permission->user($userId)->Blocks($iblockId, $blocksId);

        $rubrics = new _Rubric\Rubric();

        return $rubrics->setUserId($userId)->getAll($params);
    } 
    
    public function getRubricIndexPage(int $elId, array $params, int $userId): array
    {
        // check permission
        // $this->permission->user($userId)->Blocks($iblockId, $blocksId);

        $rubric_page = new _Rubric\Rubric();

        return $rubric_page->setUserId($userId)->getCurrent((int) $elId, (array) $params);
    } 

    protected function prepareReturn(array $array, string $typeView = self::TYPE_LIST): array
    {
        $sizePreview = $this->getSizeImage($typeView);
        if($array) {
            // prepare picture
            if(is_numeric($array['PICTURE'])) {
                $array['PICTURE'] = $this->getPictureSrc((int) $array['PICTURE'], $sizePreview);
            }

            // // prepare detail picture
            // if(is_numeric($array['DETAIL_PICTURE'])) {
            //     $array['DETAIL_PICTURE'] = $this->getPictureSrc((int) $array['DETAIL_PICTURE'], $sizePreview);
            // } else {
            //     $array['DETAIL_PICTURE']['ORIGINAL'] = $array['DETAIL_PICTURE']['RESIZE'] = Product::IMAGE_NOT_FOUND;
            // }


            // // prepare preview picture
            // if(is_numeric($array['PREVIEW_PICTURE'])) {
            //     $array['PREVIEW_PICTURE'] = $this->getPictureSrc((int) $array['PREVIEW_PICTURE'], $sizePreview);
            // } else {
            //     $array['PREVIEW_PICTURE']['ORIGINAL'] = $array['PREVIEW_PICTURE']['RESIZE'] = Product::IMAGE_NOT_FOUND;
            // }
        }

        return $array;
    }


}
