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
    public const FIELD_ELEMENT = [
        'ID', 'IBLOCK_ID', 'CODE', 'XML_ID', 'NAME', 'ACTIVE', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO', 'SORT',
        'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 'DETAIL_TEXT', 'DETAIL_TEXT_TYPE', 'DATE_CREATE', 'CREATED_BY', 'TAGS',
        'TIMESTAMP_X', 'MODIFIED_BY', 'IBLOCK_SECTION_ID', 'DETAIL_PAGE_URL', 'DETAIL_PICTURE', 'PREVIEW_PICTURE'
    ];

    public const FIELD_ELEMENT_REQUEST = [
        'ID', 'CODE'
    ];

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

        $params = $this->prepareFilterRubric($params);
        
        $rubric_page = new _Rubric\Rubric();

        return $rubric_page->setUserId($userId)->getCurrentRubric((int) $elId, (array) $params);
    } 

    protected function prepareFilterRubric(array $params): array
    {
        $return = [];
        $filter = [];
        $filterDefault = ['ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y']; 

        if(isset($params['filter']['IBLOCK_ID'])) 
            $filterDefault['IBLOCK_ID'] = $params['filter']['IBLOCK_ID'];  

        // config
        $params['limit'] =
            (int)(
                is_numeric($params['limit']) && $params['limit'] > 0 ?
                $params['limit']
                : $this->config->getResponseElementsLimit() ?? self::DEFAULT_LIMIT_PAGE
            );

        // if(empty($params['order'])) {
        //     $params['order'] = ['ID' => 'DESC'];
        // }


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
        // $return['order'] = (array)(['ID' => $params['order']] ?? ['ID' => 'DESC']);
        $return['filter'] = (array)$filter;
        $return['filter_default'] = $filterDefault;
        $return['search'] = (string)$params['search'];

        return $return;
    } 


}
