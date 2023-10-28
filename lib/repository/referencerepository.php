<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository;

use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Repository\Reference as _Reference,
    Sotbit\RestAPI\Exception\ReferenceException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l;

class ReferenceRepository extends BaseRepository
{
    
    public function __construct()
    {
        parent::__construct();
        if(!Loader::includeModule("iblock")) {
            throw new ReferenceException(l::get('ERROR_MODULE_IBLOCK'), StatusCode::HTTP_BAD_REQUEST);
        }
    } 

    public function getUserId()
    {
        return $this->userId;
    } 
    
     
    public function getReferencesTypeList(): array
    {
        // check permission
        // $this->permission->user($userId)->Blocks($iblockId, $blocksId); 

        $References = new _Reference\References();

        return $References->setUserId($userId)->getAll();
    }  

     
    public function getCourtsList(array $params): array
    {
        // check permission
        // $this->permission->user($userId)->Blocks($iblockId, $blocksId); 

        $References = new _Reference\Courts();

        return $References->setUserId($userId)->getAll($params);
    }

}
