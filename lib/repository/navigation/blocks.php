<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository\Index;

use Slim\Http\StatusCode;
use Sotbit\RestAPI\Exception\IndexException;
use Sotbit\RestAPI\Exception\OrderException,
    Sotbit\RestAPI\Core,
    Sotbit\RestAPI\Localisation as l,
    Sotbit\RestAPI\Repository\IndexRepository;

use Bitrix\Sale,
    Bitrix\Main\Entity,
    Bitrix\Main\Loader,
    Bitrix\Main\Type\DateTime,
    Bitrix\Main\UserTable,
    Bitrix\Sale\Cashbox\CheckManager,
    Bitrix\Main\Config\Option;

class Blocks extends IndexRepository
{
    public function get(array $params): array
    {
        $result = [];

        $blocks = explode(",", $params['blocks']);

        // if(count($blocks) > 1) {
        //     throw new IndexException(l::get('ERROR_INDEX_BLOCKS_BAD_TYPE'), StatusCode::HTTP_BAD_REQUEST);
        // }
        // if($this->getUserId() === null) {
        //     throw new IndexException(l::get('EMPTY_USER_ID'), StatusCode::HTTP_UNAUTHORIZED);
        // }

        $arSelect = array("ID", "NAME", "USER_NAME",);
        $navParams = ['nTopCount' => $params['limit']];  //   default = 3 
        $blockTypes  = \CIBlock::GetList(array("SORT" => "ASC"), ['TYPE' => 'publications']);

        while ($getBlockType = $blockTypes->fetch()) {

            $elements = [];

            if (in_array($getBlockType['CODE'], $blocks)) {

                $blockEls = \CIBlockElement::GetList(array("ID" => "DESC"),  array("ACTIVE" => "Y", "IBLOCK_CODE" => $getBlockType["CODE"]),  false,  $navParams, $arSelect);

                while ($element = $blockEls->fetch()) {
                    array_push($elements,  $this->prepareReturn($element, self::TYPE_DETAIL));
                }

                 $result[$getBlockType['CODE']] = [
                        "INDEX_BLOCK_ID" => $getBlockType['ID'],
                        "INDEX_BLOCK_ELEMENTS" => $elements,
                 ];
            }
        }

        // check isset
        if (!$result) {
            throw new IndexException(l::get('ERROR_INDEX_BLOCKS_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        }

        $result = $this->prepareReturn($result, self::TYPE_LIST);

        return $result;
    }

    public function list(array $params): array
    {
        $result = [];
        $navParams = ['nPageSize' => $params['limit'], 'iNumPage' => $params['page']];
        $r = \CIBlockSection::GetList($params['order'], $params['filter'], false, $params['select'], $navParams);

        while ($l = $r->fetch()) {
            $result['data'][$l['ID']] = $this->prepareReturn($l, self::TYPE_LIST);
        }

        // count all
        $countAll = \CIBlockSection::GetList([], $params['filter_default'], false, ['ID']);

        // info
        $result['info']['count_select'] = count($result);
        $result['info']['count_all'] = (int)$countAll->SelectedRowsCount();

        return $result;
    }
}
