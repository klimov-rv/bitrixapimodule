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
    
    public function getCurrent(int $elID, array $params): array
    {
        $result = [];  
  
        $res = \CIBlockElement::GetByID($elID);
        if($ar_res = $res->GetNext()) {  

            $params['filter'] = (array)(['TYPE' => 'publications']); 
            $rubric_data = [
                "RUBRIC_ID" => $ar_res['ID'],
                "RUBRIC_CODE" => $ar_res['CODE'],
                "RUBRIC_NAME" => $ar_res['NAME'],];
            $elements = $this->getElements($params, $elID, $rubric_data);  
            
            $result  = $elements; 
        }  

        // check isset
        if (!$result) {
            throw new RubricException(l::get('ERROR_INDEX_BLOCKS_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        }

        $result = $this->prepareReturn($result, self::TYPE_LIST);

        return $result;
    }

    
    public function getElements(array $params, int $elID = null, array $rubric_data = []): array
    {
        if ($elID) {
            $elements = [];
            $blockTypes = \CIBlock::GetList( [], $params['filter']); 
            // общий лимит для всей выдачи
            // $limitIterator = 0;
            while ($getBlockType = $blockTypes->fetch()) { 
    
                $prepareElements = [];
    
                $params['order'] = (array)($params['order'] ?? ['ID' => 'DESC']); 
                $params['filter'] = (array)(["ACTIVE" => "Y", "IBLOCK_CODE" => $getBlockType["CODE"]]);  
                $params['select'] = (array)(["ID", "IBLOCK_ID", "PROPERTY_PUB_RUBRIC"]);  
    
                $blockEls = \CIBlockElement::GetList(
                    array(),  
                    $params['filter'],
                    false,      
                    false  ,
                    array(),  
                ); 
                // отдельный лимит для каждого типа публикации
                // $limitIterator = 0;
                while ($element = $blockEls->GetNextElement()){  
                    
                    $arProps = $element->GetProperties();  
                    // рубрика
                    // \Bitrix\Main\Diag\Debug::dumpToFile($arProps["PUB_RUBRIC"]["VALUE"], $varName = '$arPropsValue1', $fileName = 'dumpToFile.txt'); 
                    // тематики
                    // \Bitrix\Main\Diag\Debug::dumpToFile($arProps["PUB_THEMES"]["VALUE"], $varName = '$arPropsValue2', $fileName = 'dumpToFile.txt'); 
                    
                    if ($arProps["PUB_RUBRIC"]["VALUE"] === (string)$elID && ((string)$limitIterator < $params['limit'])) {  
                        \Bitrix\Main\Diag\Debug::dumpToFile($arFields["CREATED_BY"], $varName = '$CREATED_BY', $fileName = 'dumpToFile.txt');  
                        $prepareEl = []; 
                        $arFields = $element->GetFields();    
                        $prepareEl["ID"] = $arFields["ID"]; 
                        $prepareEl["NAME"] = $arFields["NAME"]; 
                        $prepareEl["CODE"] = $arFields["CODE"];
                        $prepareEl["TYPE"] = $getBlockType['CODE'];
                        $prepareEl["CREATED_DATE"] = $arFields["DATE_CREATE"];
                        $prepareEl["USER_NAME"] = $arFields["USER_NAME"];  

                        // TODO вынести в метод или переиспользовать
                        $res = UserTable::getById($arFields["CREATED_BY"]);

                        if (!($user = $res->fetch())) {
                            $prepareEl["USER_PHOTO"] = 'images/nophoto.jpg';
                        } 
                        $prepareEl["USER_PHOTO"] = \CFile::GetPath($user['PERSONAL_PHOTO']); 

                        $prepareEl["PICTURE"] = [];
                        if (isset($arFields["PREVIEW_PICTURE"])) {
                            $prepareEl["PICTURE"] = array_push($prepareEl["PICTURE"], (int)($arFields["PREVIEW_PICTURE"]));   
                        } else {
                            $prepareEl["PICTURE"] = ["ORIGINAL" => self::IMAGE_NOT_FOUND, "RESIZE" => self::IMAGE_NOT_FOUND];
                        }
                        $prepareEl = array_merge($prepareEl, (array)($rubric_data)); 
                        $prepareEl = $this->prepareReturn($prepareEl);
                         
                        array_push($prepareElements,  $prepareEl); 

                        $limitIterator++; 
                    }
                } 
                $elements = array_merge($elements, (array)($prepareElements));  
            } 
            
    
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
    
        }
        
        return $result = $elements;




        
    }
}
