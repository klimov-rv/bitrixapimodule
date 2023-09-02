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

    
    public function get(array $params) {
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

        $data = $this->getElements([
            'filter' => [
                'ID' => $params['ID'],
                'IBLOCK_CODE' => $params['IBLOCK_CODE']
            ],
            'limit' => 1
        ],
        self::TYPE_DETAIL);

        if(!$data) {
            throw new PublicationException(l::get('ERROR_PUBLICATION_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        } 
        
        // привязка продуктов
        // $product = $this->getProducts([$id]);
 
        // $data['PRODUCT'] = $product[$id]; 
        // collect data

        $result['data'] = $data ?: [];

        return $result;
    }

    // Тут возможно будем встраивать торговые предложения подписок
    // public function getProducts(array $elementIds): array
    // {
    //     $products = [];

    //     if(count($elementIds)) {


    //         $existOffers = \CCatalogSKU::getExistOffers($elementIds);

    //         // offers id
    //         $arOffersIds = $existOffers ? array_keys(array_filter($existOffers)) : [];

    //         // no offers id
    //         $arNoOffersIds = array_diff($elementIds, $arOffersIds);

    //         // ratio
    //         $productRatioList = ProductTable::getCurrentRatioWithMeasure($arNoOffersIds);


    //         // products
    //         $select = ['ID', 'TYPE', 'AVAILABLE',
    //             'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO',
    //             'WEIGHT', 'WIDTH', 'HEIGHT', 'LENGTH',
    //             'BARCODE_MULTI',
    //             'MEASURE',

    //             'VAT_ID', 'VAT_INCLUDED'
    //         ];
    //         $select = array_merge($select, \Bitrix\Catalog\Product\SystemField::getFieldList());

    //         $req = ProductTable::getList(
    //             [
    //                 'select' => $select,
    //                 'filter' => ['@ID' => $elementIds],
    //             ]
    //         );

    //         while($product = $req->fetch()) {

    //             if(in_array($product['ID'], $elementIds)) {

    //                 $product['TYPE_NAME'] = null;
    //                 $product['TYPE_IS_OFFER'] = "N";

    //                 // product type
    //                 if(isset($product['TYPE'], $this->productTypes[$product['TYPE']])) {
    //                     // type in words
    //                     $product['TYPE_NAME'] = $this->productTypes[$product['TYPE']];

    //                     // check type offers
    //                     $product['TYPE_IS_OFFER'] = ((int)$product['TYPE'] === \Bitrix\Catalog\ProductTable::TYPE_SKU)
    //                         ? 'Y' : 'N';
    //                 }


    //                 // product ratio
    //                 if(!empty($productRatioList) && isset($productRatioList[$product['ID']])) {
    //                     $product['MEASURE_RATIO']
    //                         = $product['DEFAULT_QUANTITY'] = $productRatioList[$product['ID']]['RATIO'];
    //                     $product['MEASURE_NAME'] = $productRatioList[$product['ID']]['MEASURE']['~SYMBOL_RUS'];
    //                 }
    //             }

    //             // config: show quantity
    //             if(!$this->config->isShowQuantity()) {
    //                 $product['QUANTITY'] = null;
    //             }

    //             // sort array
    //             if(is_array($product)) {
    //                 ksort($product);
    //             }

    //             // collect all information
    //             $products[$product['ID']] =  $product;
    //         }
    //     }

    //     return $products;
    // }



    /**
     * @param $params
     *
     * @return array
     */
    public function getElements(array $params, string $type = self::TYPE_LIST): array
    {
        $elements = [];

        $navParams = ['nPageSize' => $params['limit'], 'iNumPage' => $params['page']];
        $params['select'] = empty($params['select'])? self::FIELD_ELEMENT : array_unique(array_merge($params['select'], self::FIELD_ELEMENT_REQUEST));
       
        $arFilter = $params['filter'];
        
        if($type === self::TYPE_DETAIL) {
            $arSelect = Array("ID", "NAME", "USER_NAME", "PREVIEW_PICTURE", "DETAIL_PICTURE", 'DETAIL_TEXT', 'DETAIL_TEXT_TYPE');
        } else {
            $arSelect = Array("ID", "NAME", "USER_NAME", "PREVIEW_PICTURE");
        }
        // $req = \CIBlockElement::GetList(Array(),            $arFilter,          false,      Array("nPageSize"=>10),    $params['select']);
        $req = \CIBlockElement::GetList($params['order'],   $params['filter'],  false,      $navParams,                 $arSelect); 


        while ($element = $req->fetch()) { 
            array_push($elements,  $this->prepareReturn($element, self::TYPE_DETAIL));
            // \Bitrix\Main\Diag\Debug::dumpToFile($elements, $varName = '$elements', $fileName = 'dumpToFile.txt');
        } 


        return $elements;
    }

    public function list(array $params)
    {
        if($this->getUserId() === null) {
            throw new PublicationException(l::get('EMPTY_USER_ID'), StatusCode::HTTP_UNAUTHORIZED);
        }
 
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
                $array['DETAIL_PICTURE']['ORIGINAL'] = $array['DETAIL_PICTURE']['RESIZE'] = Product::IMAGE_NOT_FOUND;
            }

 
            // prepare preview picture
            if(is_numeric($array['PREVIEW_PICTURE'])) {
                $array['PREVIEW_PICTURE'] = $this->getPictureSrc((int) $array['PREVIEW_PICTURE'], $sizePreview);
            } else {
                $array['PREVIEW_PICTURE']['ORIGINAL'] = $array['PREVIEW_PICTURE']['RESIZE'] = Product::IMAGE_NOT_FOUND;
            }
        }

        return $array;
    }
}
