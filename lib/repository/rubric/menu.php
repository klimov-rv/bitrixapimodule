<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository\Navigation;

use Slim\Http\StatusCode;
use Sotbit\RestAPI\Exception\NavigationException;
use Sotbit\RestAPI\Exception\OrderException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l,
    Sotbit\RestAPI\Repository\NavigationRepository;

use Bitrix\Sale,
    Bitrix\Main\Entity,
    Bitrix\Main\Loader,
    Bitrix\Main\Type\DateTime,
    Bitrix\Main\UserTable,
    Bitrix\Sale\Cashbox\CheckManager,
    Bitrix\Main\Config\Option;

class Menu extends NavigationRepository
{
    public function get(array $params): array
    {
        $result = []; 
        $collect = [];

        $params['order'] = (array)($params['order'] ?? ['ID' => 'DESC']); 
        $params['filter'] = (array)(["ACTIVE" => "Y", "IBLOCK_CODE" => $params['menu_code']]);
         
        $params['limit']  = isset($params['limit']) ? (array)(['nPageSize' => $params['limit']]) : false;
        $params['select'] = (array)(["ID", "IBLOCK_ID", "CODE", "NAME",]); 
        
        // iblock elements
        $collect['ELEMENTS'] = $this->getElements($params, self::TYPE_LIST);

        // collect all information
        foreach ($collect['ELEMENTS'] as $elementId => $elementData) {
            $result[$elementId] = $elementData;
        }

        return $result;
    }

    public function getElements(array $params, string $type = self::TYPE_LIST): array
    {
        $elements = []; 
        $req = \CIBlockElement::GetList(
            $params['order'],  
            $params['filter'],
            false,      
            $params['limit'],  
            $params['select']
        ); 
        
        //   ["NAME"]=>
        //   string(44) "Как пункт в главном меню"
        //   ["CODE"]=>
        //   string(12) "TO_MAIN_MENU"


        //   ["NAME"]=>
        // string(39) "Иконка в главном меню" 
        // ["CODE"]=>
        // string(10) "THEME_ICON" 

        while($element = $req->GetNextElement()){ 

            $prepare = []; 

            $arFields = $element->GetFields();   

            // $prepare["ID"] = $arFields["ID"]; 
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

        // $result = $this->prepareReturn($elements, self::TYPE_LIST); 

        return $result = $elements;
    }
}
