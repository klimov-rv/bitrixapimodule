<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository\Publication;

use PHPUnit\Util\Exception;
use Slim\Http\StatusCode;
use Sotbit\RestAPI\Exception\PublicationException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l,
    Sotbit\RestAPI\Repository\PublicationRepository;


class Permission extends PublicationRepository
{
    public $userId;
    public const PERMISSION_DENIED = 'D';
    public const PERMISSION_READ = 'R';
    public const PERMISSION_WRITE = 'W';
    public const PERMISSION_ALL = 'X';

    public function __construct()
    {
    }

    /**
     * @param  mixed  $userId
     */
    public function user($userId): Permission
    {
        if(!$userId) {
            throw new PublicationException(l::get('EMPTY_USER_ID'), StatusCode::HTTP_UNAUTHORIZED);
        }
        $this->userId = $userId;
        return $this;
    }


    /**
     * Check access get CATALOG for current user
     *
     * @param  int  $iblockId
     * @param  int  $sectionId
     *
     * @throws PublicationException
     */
    public function section(int $iblockId, int $sectionId): void
    {
        if(!$iblockId) {
            throw new PublicationException(l::get('ERROR_CATALOG_ID_EMPTY'), StatusCode::HTTP_BAD_REQUEST);
        }

        // type rights [E - extended, S - normal] ,
        $rightsMode = \CIBlock::GetArrayByID($iblockId, "RIGHTS_MODE");
        if($rightsMode === 'E') {

            if($sectionId > 0)
            {
                $obRights = new \CIBlockSectionRights($iblockId, $sectionId);
            } else {
                $obRights = new \CIBlockRights($iblockId);
            }
            $rights = $obRights->GetUserOperations($iblockId, $this->userId);

            if(!in_array('section_read', $rights)) {
                throw new PublicationException(l::get('ERROR_CATALOG_PERMISSION_DENIED'), StatusCode::HTTP_BAD_REQUEST);
            }
        } else {
            $permission = \CIBlock::GetPermission($iblockId, $this->userId);
            if($permission < self::PERMISSION_READ) {
                throw new PublicationException(l::get('ERROR_CATALOG_PERMISSION_DENIED'), StatusCode::HTTP_BAD_REQUEST);
            }
        }
    }

    public function product(int $productId): void
    {
        $iblockId = \CIBlockElement::GetIBlockByID($productId);

        if(!$iblockId) {
            throw new PublicationException(l::get('ERROR_CATALOG_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        }

        // type rights [E - extended, S - normal] ,
        $rightsMode = \CIBlock::GetArrayByID($iblockId, "RIGHTS_MODE");
        if($rightsMode === 'E') {

            if($sectionId > 0)
            {
                $obRights = new \CIBlockSectionRights($iblockId, 0);
            } else {
                $obRights = new \CIBlockRights($iblockId);
            }
            /*$obRights = new CIBlockElementRights($IBLOCK_ID, $ID);
            $htmlHidden = '';
            foreach($obRights->GetRights() as $RIGHT_ID => $arRight)*/
            $rights = $obRights->GetUserOperations($iblockId, $this->userId);

            if(!in_array('element_read', $rights)) {
                throw new PublicationException(l::get('ERROR_CATALOG_PERMISSION_DENIED'), StatusCode::HTTP_BAD_REQUEST);
            }
        } else {
            $permission = \CIBlock::GetPermission($iblockId, $this->userId);
            if($permission < self::PERMISSION_READ) {
                throw new PublicationException(l::get('ERROR_CATALOG_PERMISSION_DENIED'), StatusCode::HTTP_BAD_REQUEST);
            }
        }

        //throw new CatalogException(l::get('ERROR_CATALOG_PRODUCT_PERMISSION_DENIED'), StatusCode::HTTP_BAD_REQUEST);
    }
}
