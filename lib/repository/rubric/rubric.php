<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository\Rubric;

use Slim\Http\StatusCode;
use Sotbit\RestAPI\Exception\RubricException;
use Sotbit\RestAPI\Exception\OrderException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l,
    Sotbit\RestAPI\Repository\RubricRepository;

use Bitrix\Sale,
    Bitrix\Main\Entity,
    Bitrix\Main\Loader,
    Bitrix\Main\Type\DateTime,
    Bitrix\Main\UserTable,
    Bitrix\Sale\Cashbox\CheckManager,
    Bitrix\Main\Config\Option;

class Rubric extends RubricRepository
{
    public function getAll(array $params): array
    {
        $result = []; 
        $collect = [];

        $params['order'] = (array)($params['order'] ?? ['ID' => 'DESC']); 
        $params['filter'] = (array)(["ACTIVE" => "Y", "IBLOCK_CODE" => $params['menu_code']]);
         
        $params['limit']  = isset($params['limit']) ? (array)(['nPageSize' => $params['limit']]) : false;
        $params['select'] = (array)(["ID", "IBLOCK_ID", "CODE", "NAME", "USER_NAME"]); 
        
        // iblock elements
        $collect['ELEMENTS'] = $this->getElements($params, $elID);

        // collect all information
        foreach ($collect['ELEMENTS'] as $elementId => $elementData) {
            $result[$elementId] = $elementData;
        }

        return $result;
    }
    
    public function getCurrentRubric(int $elID, array $params): array
    {
        $result = [];  
  
        $res = \CIBlockElement::GetByID($elID);
        if($ar_res = $res->GetNext()) {  

            $params['filter'] = (array)(['TYPE' => 'publications']); 
            $rubric_data = [
                "RUBRIC_ID" => $ar_res['ID'],
                "RUBRIC_CODE" => $ar_res['CODE'],
                "RUBRIC_NAME" => $ar_res['NAME']];
            $elements = $this->getElements($params, $elID, $rubric_data);  
            
            $result  = $elements; 
        }   
        
        // check isset
        if (!$result) {
            throw new RubricException(l::get('ERROR_RUBRIC_BLOCKS_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        }

        $result = $this->prepareReturn($result, self::TYPE_LIST);

        return $result;
    }
  
    public function getElements(array $params, int $elID = null, array $rubric_data = []): array
    {
        if ($elID) {
            $elements = [];

            $navParams = ['nPageSize' => $params['limit'], 'iNumPage' => $params['page']];
            $arFilter = (array)(["ACTIVE" => "Y", "PROPERTY_PUB_RUBRIC" => (string)$elID  ]); 
            $arSelect = Array();  

            $req = \CIBlockElement::GetList(
                array(),   
                $arFilter,  
                false,      
                $navParams,                 
                $arSelect
            ); 
     
            while ($el = $req->fetch()) { 
                $prepareEl = [];
                 
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
                $prepareEl = array_merge($prepareEl, (array)($rubric_data));  
                array_push($elements,  $this->prepareReturn($prepareEl, self::TYPE_DETAIL)); 
            } 

            
        $result[$rubric_data["RUBRIC_CODE"]] = $elements; 

        // count all
        $countAll = \CIBlockElement::GetList([], $arFilter, false, ['ID','PROPERTY_PUB_RUBRIC']); 

        // info
        $result['info']['rubric_name'] = $rubric_data['RUBRIC_NAME'];
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
