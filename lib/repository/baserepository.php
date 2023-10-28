<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository;

use Bitrix\Iblock\Model\PropertyFeature;
use Bitrix\Main\Context;
use Bitrix\Currency;
use Sotbit\RestAPI\Config\Config;
use Sotbit\RestAPI\Core\Helper;
use Sotbit\RestAPI\Core;

abstract class BaseRepository
{
    public Config $config;
    public $siteId;
    protected $userId;

    /**
     * Default limit of messages per page
     */
    public const DEFAULT_LIMIT_PAGE = 10;

    public const IMAGE_PREVIEW = 200;
    public const TYPE_LIST = 'list';
    public const TYPE_DETAIL = 'detail';
    
    public const IMAGE_NOT_FOUND = '/bitrix/components/bitrix/catalog.section/templates/.default/images/no_photo.png';


    public function __construct()
    {
        // get config
        $this->config = Config::getInstance();

        $this->setSiteId();
    }

    /**
     * @return mixed
     */
    public function setSiteId()
    {
        $this->siteId = Context::getCurrent()->getSite();
    }

    /**
     * @return mixed
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId ? (int)$this->userId : null;
    }

    /**
     * @param  mixed  $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    public function isPropertyFeature()
    {
        if(class_exists(PropertyFeature::class)) {
            return PropertyFeature::isEnabledFeatures();
        }

        return false;
    } 
    protected function prepareNavigationBase(array $params): array
    {
        $result = [];
        $filter = [];
        // if(is_array($params['filter'])) {
        //     $filter = array_map(
        //         function($v) {
        //             return $this->prepareVariable($v);
        //         },
        //         $params['filter']
        //     );
        // }

        // $result['select'] = (array)($params['select'] ? $this->prepareVariable($params['select']) : ['*']);
        
        $result['select'] = (array)(['*']);
        $result['page'] = (int)(is_numeric($params['page']) && $params['page'] > 0 ? $params['page']
            : 1);
        $result['limit'] = (int)(is_numeric($params['limit']) && $params['limit'] > 0 && $params['limit'] < self::DEFAULT_LIMIT_PAGE ? $params['limit']
            : self::DEFAULT_LIMIT_PAGE);
        $result['order'] = (array)($params['order'] ?? ['ID' => 'DESC']);
        $result['filter'] = (array)$filter;

        return $result;
    }

    protected function prepareFilterTypePublication(array $params): array
    {
        $return = [];
        $filter = [];
        $filterDefault = ['ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'];

        if(isset($params['filter']['IBLOCK_ID'])) {
            $filterDefault['IBLOCK_ID'] = $params['filter']['IBLOCK_ID'];
            $filterDefault['IBLOCK_CODE'] = (string)$params['publication_type'];
        } else {
            $filterDefault['IBLOCK_CODE'] = (string)$params['publication_type'];
        }

        // if($params['user_id']) {
        //     $filterDefault['CHECK_PERMISSIONS'] = 'Y';
        //     $filterDefault['MIN_PERMISSION'] = 'R';
        //     $filterDefault['PERMISSIONS_BY'] = (int)$params['user_id'];
        // }

        // config
        $params['limit'] =
            (int)(
                is_numeric($params['limit']) && $params['limit'] > 0 ?
                $params['limit']
                : $this->config->getResponseElementsLimit() ?? self::DEFAULT_LIMIT_PAGE
            );

        if(empty($params['order'])) {
            $params['order'] = ['ID' => 'DESC'];
        }

        if(is_array($params['filter'])) {
            $filter = array_map(
                function($v) {
                    return Helper::convertEncodingToSite($v);
                },
                $params['filter']
            );
        }
        $filter = array_merge($filterDefault, $filter);

 
        // $result['select'] = (array)($params['select'] ? $this->prepareVariable($params['select']) : ['*']);
        $result['select'] = (array)(['*']); 

        $return['page'] = (int)(is_numeric($params['page']) && $params['page'] > 0 ? $params['page']
            : 1);
        $return['limit'] = (int)(is_numeric($params['limit']) && $params['limit'] > 0 ? $params['limit']
            : self::DEFAULT_LIMIT_PAGE);
        $return['order'] = (array)(['ID' => $params['order']] ?? ['ID' => 'DESC']);
        $return['filter'] = (array)$filter;
        $return['filter_default'] = $filterDefault;
        $return['search'] = (string)$params['search'];

        return $return;
    }
 
       /**
     * Prepare picture and etc
     *
     * @param  array  $array
     *
     * @return array
     */
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


    public function getSizeImage(string $type = self::TYPE_LIST)
    {
        if($type === self::TYPE_DETAIL) {
            return \Bitrix\Main\Config\Option::get('iblock', 'detail_image_size', self::IMAGE_PREVIEW);
        }
        return \Bitrix\Main\Config\Option::get('iblock', 'list_image_size', self::IMAGE_PREVIEW);
    }

    public function getPictureSrc(int $id, $sizePreview = null)
    {
        $image = [];
        if(!$sizePreview) {
            $sizePreview = $this->getSizeImage(self::TYPE_LIST);
        }

        if($id) {
            $imageOriginal = \CFile::GetFileArray($id);

            $imageResize = \CFile::ResizeImageGet(
                $id,
                ["width" => $sizePreview, "height" => $sizePreview],
                BX_RESIZE_IMAGE_PROPORTIONAL,
                false
            );
        }
        if(is_array($imageOriginal)) {
            $image['ORIGINAL'] = $imageOriginal['SRC'];
        }
        if(is_array($imageResize)) {
            $image['RESIZE'] = $imageResize['src'];
        }

        return $image;
    }
 
}