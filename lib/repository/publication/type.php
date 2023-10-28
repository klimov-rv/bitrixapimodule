<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository\Publication;

use Slim\Http\StatusCode;
use Sotbit\RestAPI\Exception\PublicationException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l,
    Sotbit\RestAPI\Repository\PublicationRepository;

use Bitrix\Sale,
    Bitrix\Main\Entity,
    Bitrix\Main\Loader,
    Bitrix\Main\Type\DateTime,
    Bitrix\Main\UserTable,
    Bitrix\Sale\Cashbox\CheckManager,
    Bitrix\Main\Config\Option,
    Bitrix\Catalog\ProductTable,
    Bitrix\Currency,
    Bitrix\Iblock;

class Type extends PublicationRepository
{
    
    public const FIELD_ELEMENT = [
        'ID', 'IBLOCK_ID', 'CODE', 'XML_ID', 'NAME', 'ACTIVE', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO', 'SORT',
        'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 'DETAIL_TEXT', 'DETAIL_TEXT_TYPE', 'DATE_CREATE', 'CREATED_BY', 'TAGS',
        'TIMESTAMP_X', 'MODIFIED_BY', 'IBLOCK_SECTION_ID', 'DETAIL_PAGE_URL', 'DETAIL_PICTURE', 'PREVIEW_PICTURE'
    ];

    public const FIELD_ELEMENT_REQUEST = [
        'ID', 'CODE'
    ];

    
    public function getOne(array $params) 
    {
        $result = [];

        if(!$params['ID']) {
            throw new PublicationException(l::get('ERROR_PUBLICATION_ID_EMPTY'), StatusCode::HTTP_BAD_REQUEST);
        }
        if($this->getUserId() === null) {
            throw new PublicationException(l::get('EMPTY_USER_ID'), StatusCode::HTTP_UNAUTHORIZED);
        }

        // get and check iblock
        $iblockId = \CIBlockElement::GetIBlockByID($params['ID']);
        if(!$iblockId) {
            throw new PublicationException(l::get('ERROR_PUBLICATION_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        } 
        $params['filter'] =  [ 'ID' => $params['ID'], 'IBLOCK_ID' => $params['IBLOCK_ID'], 'IBLOCK_CODE' => $params['IBLOCK_CODE'] ];
        // $data = $this->getElements([
        //     'filter' => [
        //         'ID' => $params['ID'],
        //         'IBLOCK_CODE' => $params['IBLOCK_CODE']
        //     ],
        //     'limit' => 1
        // ], 
        $navParams = ['nPageSize' => $params['limit'], 'iNumPage' => $params['page']];
        $arSelect = Array("ID", "IBLOCK_ID", "NAME", "USER_NAME", "DATE_CREATE", "CREATED_BY", "PREVIEW_PICTURE", "DETAIL_PICTURE", "DETAIL_TEXT", "DETAIL_TEXT_TYPE", "PROPERTY_PUB_THEMES" );

        $req = \CIBlockElement::GetList($params['order'],   $params['filter'],  false,      $navParams,                 $arSelect); 

        $tag_elems = [];
        $prepareEl = [];
        // PROPERTY_PUB_THEMES_VALUE	"393"
        // $res = \CIBlockElement::GetByID($element["PROPERTY_PUB_THEMES_VALUE"]);  
             
        if ($ob = $req->GetNextElement()){ 
            $arProps = $ob->GetProperties(); 
            $arField = $ob->GetFields();
            $prepareEl["ID"] = $arField["ID"]; 
            $prepareEl["NAME"] = $arField["NAME"];  
            $prepareEl["PREVIEW_PICTURE"] = $arField["PREVIEW_PICTURE"];
            $prepareEl["DETAIL_TEXT"] = $arField["DETAIL_TEXT"];
            $prepareEl["CREATED_DATE"] = $arField["DATE_CREATE"];
            $prepareEl["USER_NAME"] = $arField["USER_NAME"];   
            
            foreach ($arProps["PUB_THEMES"]["VALUE"] as $propId) {
                $res = \CIBlockElement::GetByID($propId);
                if($ar_res = $res->GetNext()) { 
                    array_push($tag_elems, [
                        "PUB_THEME_ID" => $ar_res['ID'],
                        "PUB_THEME_NAME" => $ar_res["NAME"],
                        "PUB_THEME_CODE" => $ar_res['CODE']]);
                }
                $prepareEl['PUB_THEME_TAGS'] = $tag_elems;
            }
            
            // \Bitrix\Main\Diag\Debug::dumpToFile($arField, $varName = '$arField', $fileName = 'dumpToFile.txt');
            $respn = UserTable::getById($arField["CREATED_BY"]);

            if (!($user = $respn->fetch())) {
                $prepareEl["USER_PHOTO"] = 'images/nophoto.jpg';
            } 
            $prepareEl["USER_PHOTO"] = \CFile::GetPath($user['PERSONAL_PHOTO']); 


            $data = $this->prepareReturn($prepareEl, self::TYPE_DETAIL);
        }
        

        if(!$data) {
            throw new PublicationException(l::get('ERROR_PUBLICATION_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        }

        $result['data'] = $data ?: [];

        return $result;
    }
    
    public function getElements(array $params, string $type = self::TYPE_LIST): array
    {
        $elements = [];

        $navParams = ['nPageSize' => $params['limit'], 'iNumPage' => $params['page']];
        $params['select'] = empty($params['select'])? self::FIELD_ELEMENT : array_unique(array_merge($params['select'], self::FIELD_ELEMENT_REQUEST));
        $arSelect = Array("ID",  "NAME", "USER_NAME", "PREVIEW_PICTURE", "PROPERTY_PUB_THEMES.NAME" ); 
         
        $req = \CIBlockElement::GetList($params['order'],   $params['filter'],  false,      $navParams,                 $arSelect); 

        while ($element = $req->fetch()) { 
            
            array_push($elements,  $this->prepareReturn($element, self::TYPE_DETAIL)); 
        }

        return $elements;
    }

    public function list(array $params)
    {
        if($this->getUserId() === null) {
            throw new PublicationException(l::get('EMPTY_USER_ID'), StatusCode::HTTP_UNAUTHORIZED);
        }
        \Bitrix\Main\Diag\Debug::dumpToFile($params, $varName = '$params', $fileName = 'dumpToFile.txt');
        $result = [];
        $data = [];
        $collect = [];

        // checking the selected infoblock for type
        $iblockId  = (int) $params['filter']['IBLOCK_ID'];
        $sectionId = (int) $params['filter']['SECTION_ID'];


        // Search
        // if search, return elements ids in filter
        if(!empty($params['search'])) {
            $searchSettings = [];
            $searchSettings['query'] = $params['search'];

            // config
            $searchSettings['iblockId'] = $this->config->getCatalogId() ?? $iblockId;
            $searchSettings['guessLanguage'] = $this->config->getSearchLanguageGuess() ?? self::SEARCH_DEFAULT_GUESS_LANGUAGE;
            $searchSettings['noWordLogic'] =  $this->config->getSearchNoWordLogic() ?? self::SEARCH_DEFAULT_NO_WORD_LOGIC;
            $searchSettings['withoutMorphology'] = $this->config->getSearchWithoutMorphology() ?? self::SEARCH_DEFAULT_WITHOUT_MORPHOLOGY;


            $searchElementIds = $this->search->setSettings($searchSettings)->execute();

            if($searchElementIds) {
                $params['filter']['ID'] = $searchElementIds;
            } else {
                $params['filter']['=ID'] = 0;
            }
        }
        
        // iblock elements
        $collect['ELEMENTS'] = $this->getElements($params, self::TYPE_LIST);

        // collect all information
        foreach($collect['ELEMENTS'] as $elementId => $elementData) {
            $data[$elementId] = $elementData;
 
        } 

        // count all
        //$countAll = \CIBlockElement::GetList([], $params['filter_default'], false, false, ['ID']);
        $countAll = \CIBlockElement::GetList([], $params['filter'], []);

        // data
        $result['data'] = $data ?: [];

        // info
        $result['info']['count_select'] = count($data) ?: 0;
        $result['info']['count_all'] = (int) $countAll;

        return $result;
    }
      
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
            } else {
                $array['DETAIL_PICTURE']['ORIGINAL'] = $array['DETAIL_PICTURE']['RESIZE'] = self::IMAGE_NOT_FOUND;
            }

 
            // prepare preview picture
            if(is_numeric($array['PREVIEW_PICTURE'])) {
                $array['PREVIEW_PICTURE'] = $this->getPictureSrc((int) $array['PREVIEW_PICTURE'], $sizePreview);
            } else {
                $array['PREVIEW_PICTURE']['ORIGINAL'] = $array['PREVIEW_PICTURE']['RESIZE'] = self::IMAGE_NOT_FOUND;
            }
        }

        return $array;
    }
}
