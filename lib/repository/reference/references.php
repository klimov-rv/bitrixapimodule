<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository\Reference;

use Slim\Http\StatusCode;
use Sotbit\RestAPI\Exception\ReferenceException;
use Sotbit\RestAPI\Exception\OrderException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l,
    Sotbit\RestAPI\Repository\ReferenceRepository;

use Bitrix\Sale,
    Bitrix\Main\Entity,
    Bitrix\Main\Loader,
    Bitrix\Main\Type\DateTime,
    Bitrix\Main\UserTable,
    Bitrix\Sale\Cashbox\CheckManager,
    Bitrix\Main\Config\Option;

class References extends ReferenceRepository
{
    public function getAll(): array
    {
        $result = [];

        // $refs = explode(",", $params['refs']);
        // $elements = [];

        // if(count($refs) > 1) {
        //     throw new IndexException(l::get('ERROR_INDEX_BLOCKS_BAD_TYPE'), StatusCode::HTTP_BAD_REQUEST);
        // }
        // if($this->getUserId() === null) {
        //     throw new IndexException(l::get('EMPTY_USER_ID'), StatusCode::HTTP_UNAUTHORIZED);
        // }
        // $result = \CIBlockSection::GetList([], ['IBLOCK_CODE' => 'news1'])->Fetch()['ID'];

        $arSelect = array("ID", "NAME", "USER_NAME",);
        $navParams = ['nTopCount' => $params['limit']];  //   default = 3 
        $blockTypes  = \CIBlock::GetList(array("SORT" => "ASC"), ['TYPE' => 'references']);

        while ($getBlockType = $blockTypes->fetch()) {

            // \Bitrix\Main\Diag\Debug::dumpToFile($getBlockType, $varName = '$getBlockType', $fileName = 'dumpToFile.txt');
            $result[$getBlockType['CODE']] = [
                "REF_BLOCK_ID" => $getBlockType['ID'],
                "REF_BLOCK_NAME" => $getBlockType['NAME'],
        ];
            // if (in_array($getBlockType['CODE'], $refs)) {

            //     $blockEls = \CIBlockElement::GetList(array("ID" => "DESC"),  array("ACTIVE" => "Y", "IBLOCK_CODE" => $getBlockType["CODE"]),  false,  $navParams, $arSelect);

            //     while ($element = $blockEls->fetch()) {
            //         array_push($elements,  $this->prepareReturn($element, self::TYPE_DETAIL));
            //     }
            //     \Bitrix\Main\Diag\Debug::dumpToFile($element, $varName = '$returnFiles', $fileName = 'dumpToFile.txt');
            //      $result[$getBlockType['CODE']] = [
            //             "INDEX_BLOCK_ID" => $getBlockType['ID'],
            //             "INDEX_BLOCK_ELEMENTS" => $elements,
            //      ];
            // }
        }

        // check isset
        if (!$result) {
            throw new ReferenceException(l::get('ERROR_INDEX_BLOCKS_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        }

        $result = $this->prepareReturn($result, self::TYPE_LIST);

        return $result;
    }

  
    public function getElements(array $params, int $elID = null, array $Reference_data = []): array
    {
        if ($elID) {
            $elements = [];

            $navParams = ['nPageSize' => $params['limit'], 'iNumPage' => $params['page']];
            // $params['select'] = empty($params['select'])? self::FIELD_ELEMENT : array_unique(array_merge($params['select'], self::FIELD_ELEMENT_REQUEST));
           
            $arFilter = (array)(["ACTIVE" => "Y", "PROPERTY_PUB_Reference" => (string)$elID  ]); 
            $arSelect = Array(); 
            // $req = \CIBlockElement::GetList(Array(),            $arFilter,          false,      Array("nPageSize"=>10),    $params['select']);
            $req = \CIBlockElement::GetList(
                array(),   
                $arFilter,  
                false,      
                $navParams,                 
                $arSelect
            ); 
     
            while ($el = $req->fetch()) { 
                $prepareEl = [];
                
            // \Bitrix\Main\Diag\Debug::dumpToFile($el, $varName = '$el', $fileName = 'dumpToFile.txt');
                $prepareEl["ID"] = $el["ID"]; 
                $prepareEl["NAME"] = $el["NAME"]; 
                $prepareEl["CODE"] = $el["CODE"];
                $prepareEl["TYPE"] = $el["IBLOCK_CODE"];
                $prepareEl["CREATED_DATE"] = $el["DATE_CREATE"];
                $prepareEl["USER_NAME"] = $el["USER_NAME"];   
                
                $respn = UserTable::getById($el["CREATED_BY"]);

                if (!($user = $respn->fetch())) {
                    $prepareEl["USER_PHOTO"] = 'images/nophoto.jpg';
                } 
                $prepareEl["USER_PHOTO"] = \CFile::GetPath($user['PERSONAL_PHOTO']); 

                $prepareEl["PICTURE"] = $el["DETAIL_PICTURE"];
                $prepareEl = array_merge($prepareEl, (array)($Reference_data));  
                array_push($elements,  $this->prepareReturn($prepareEl, self::TYPE_DETAIL)); 
            } 

            
        $result[$Reference_data["Reference_CODE"]] = $elements; 

        // count all
        $countAll = \CIBlockElement::GetList([], $arFilter, false, ['ID','PROPERTY_PUB_Reference']); 

        // info
        $result['info']['Reference_name'] = $Reference_data['Reference_NAME'];
        $result['info']['count_select'] = count($elements);
        $result['info']['count_all'] = (int)$countAll->SelectedRowsCount(); 
        
    
        } else {

            $elements = []; 
            $req = \CIBlockElement::GetList(
                $params['order'],  
                $params['filter'],
                false,      
                $params['limit'],  
                $params['select']
            ); 
             
    
            while($element = $req->GetNextElement()){ 
    
                $prepare = []; 
    
                $arFields = $element->GetFields();   
    
                $prepare["ID"] = $arFields["ID"]; 
                $prepare["NAME"] = $arFields["NAME"]; 
                $prepare["CODE"] = $arFields["CODE"];  
    
                $arProps = $element->GetProperties();
    
                if ($arProps["TO_MAIN_MENU"]['VALUE_XML_ID'] === "in_main") { 
                    $prepare['SHOW_IN_MAIN_MENU'] = true;
                } else { 
                    $prepare['SHOW_IN_MAIN_MENU'] = false;
                } 
                $filePath = \CFile::GetPath($arProps["THEME_ICON"]['VALUE']); 
                $prepare["THEME_ICON_PATH"] = $filePath;  
    
                array_push($elements,  $prepare);
            }
    
            $result = $elements; 
        }
        
        
        return $result; 
        
    }
}
