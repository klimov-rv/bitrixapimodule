<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository;

use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Repository\Publication as _Publication,
    Sotbit\RestAPI\Exception\PublicationException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l;

class PublicationRepository extends BaseRepository
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
            throw new PublicationException(l::get('ERROR_MODULE_IBLOCK'), StatusCode::HTTP_BAD_REQUEST);
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

    /**
     * Publication by type get
     *
     * @param  int  $id
     * @param  null  $userId
     *
     * @return array
     * @throws CatalogException
     */
    public function getPublicationTypeEl(array $args, int $elId, int $userId) {
        // check permission
        //$this->permission->user($userId)->publication($id);
        
        $params = 
        [
            'IBLOCK_ID' => $args['iblock_id'],
            'IBLOCK_CODE' => $args['iblock_code'],
            'ID' => $elId
        ];
        $publication = new _Publication\Type();

        return $publication->setUserId($userId)->getOne($params);

    }
    

    public function getPublicationTypeList(array $params)
    {
        $userId = $params['user_id'];

        // prepare params to catalog params
        $params = $this->prepareFilterTypePublication($params);

        // check permission
        // $this->permission->user($userId)->section($iblockId, 0); 

        $result = [];

        $type = new _Publication\Type();
        $result = $type->setUserId($userId)->list($params);

        return $result;
    }

    // public function get(int $userId): array
    // {
    //     $result = [];

    //     $user = $this->getList([
    //        'filter'        => ['=ID' => $userId],
    //        'limit'         => 1,
    //     ]);

    //     if(empty($user)) {
    //         throw new UserException(l::get('ERROR_USER_NOT_FOUND'), 404);
    //     }

    //     $user = reset($user);

    //     // Personal photo
    //     if($user['PERSONAL_PHOTO']) {
    //         $user['PERSONAL_PHOTO'] = \CFile::GetPath($user['PERSONAL_PHOTO']);
    //     }

    //     // User country
    //     $user['PERSONAL_COUNTRY'] = $user['PERSONAL_COUNTRY'] ? GetCountryByID((int)$user['PERSONAL_COUNTRY']) : null;

    //     // Birthday format
    //     if($user['PERSONAL_BIRTHDAY'] && $user['PERSONAL_BIRTHDAY'] instanceof Type\Date) {
    //         $user['PERSONAL_BIRTHDAY'] = $user['PERSONAL_BIRTHDAY']->format(
    //             Type\Date::convertFormatToPhp(\CSite::GetDateFormat('SHORT'))
    //         );
    //     }

    //     // Gender format
    //     if($user['PERSONAL_GENDER']) {
    //         $user['PERSONAL_GENDER'] = $user['PERSONAL_GENDER'] === 'M' ? l::get('USER_MALE') : l::get('USER_FEMALE');
    //     } else {
    //         $user['PERSONAL_GENDER'] = l::get('USER_DONT_KNOW');
    //     }


    //     // Get groups
    //     $getListClassName = $this->getUserClass();
    //     $groups = $getListClassName::getUserGroupIds($user['ID']);

    //     foreach($this->allowedUserFields as $key => $val) {
    //         if($key === 'groups') {
    //             $result[$key] = $groups;
    //         } else {
    //             $result[$key] = array_intersect_key($user, array_flip(array_diff($val, [''])));
    //         }
    //     }




    //     // add title for values
    //     $emptySkip = true;
    //     $reformatResult = [];
    //     foreach($result as $nameTab => $values) {
    //         if($nameTab === 'groups') {
    //             $reformatResult[$nameTab] = $values;
    //             continue;
    //         }
    //         $valuesTab = [];
    //         foreach($values as $valueName => $value) {
    //             if($emptySkip && !$value) {
    //                 continue;
    //             }
    //             $valuesTab[$valueName]['TITLE'] = l::get('USER_'.$valueName);
    //             $valuesTab[$valueName]['VALUE'] = $value;
    //         }
    //         $reformatResult[$nameTab]['TITLE'] = l::get('USER_TITLE_'.$nameTab);
    //         $reformatResult[$nameTab]['VALUES'] = $valuesTab;
    //     }


    //     return $reformatResult;
    // }



    
    public function getSection(int $iblockId, int $sectionId, int $userId)
    {
        // check permission
        $this->permission->user($userId)->section($iblockId, $sectionId);

        $section = new _Publication\Section();

        return $section->setUserId($userId)->get($sectionId);
    }
    
    /**
     * https://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getlist.php
     *
     * @param  array  $params
     *
     * @return array
     * @throws CatalogException
     */
    public function getSectionsList(array $params)
    {
        $userId = $params['user_id'];

        // prepare params to catalog params
        $params = $this->prepareNavigationCatalog($params);

        //$params['filter']['IBLOCK_ID'] = $params;


        // check permission
        $this->permission->user($userId)->section((int) $params['filter']['IBLOCK_ID'], 0);

        $section = new _Publication\Section();

        return $section->setUserId($userId)->list($params);
    }


}
