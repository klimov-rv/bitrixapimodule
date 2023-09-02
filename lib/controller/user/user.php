<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Controller\User;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;

use Sotbit\RestAPI\Exception\UserException;
use Sotbit\RestAPI\Localisation as l;
use Respect\Validation\Validator as v;


/**
 * Class User
 *
 * @package Sotbit\RestAPI\Controller
 * @author Andrey Sapronov <a.sapronov@sotbit.ru>
 * Date: 28.01.2021
 */
class User extends Base
{
    public function getCurrent(Request $request, Response $response, array $args): Response
    {
        // $myId = $this->getUserId($request);

        // $rsUser = \CUser::GetByID($myId);
        // $arUser = $rsUser->Fetch();

        $user = $this->getRepository()->get((int)$this->getUserId($request)); 

        return $this->response($response, self::RESPONSE_SUCCESS, $user, StatusCode::HTTP_OK);
    }

    public function getOne(Request $request, Response $response, array $args): Response
    {
        $user = $this->getRepository()->get((int)$args['id']);

        return $this->response($response, self::RESPONSE_SUCCESS, $user, StatusCode::HTTP_OK);
    }
 
    public function update(Request $request, Response $response): Response
    {
        $input = (array)$request->getParsedBody();
        
        if (empty($input['name'])) {
            throw new UserException(l::get('ERROR_USER_NAME_EMPTY'), 400);
        }
        // if(empty($input['email'])) {
        //     throw new UserException(l::get('ERROR_USER_EMAIL_EMPTY'), 400);
        // }
        if (!v::email()->validate($input['email'])) {
            throw new UserException(l::get('ERROR_USER_EMAIL_INVALID'), 400);
        }
        // if(empty($input['phone'])) {
        //     throw new UserException(l::get('ERROR_USER_PHONE_EMPTY'), 400);
        // }
        // if(!v::phone()->validate($input['phone'])) {
        //     throw new UserException(l::get('ERROR_USER_PHONE_INVALID'), 400);
        // }

        $input['uploaded_files'] = $request->getUploadedFiles(); 

        $message = $this->getRepository()->updateUser($input, (int)$this->getUserId($request));

        return $this->response($response, self::RESPONSE_SUCCESS, $message, StatusCode::HTTP_OK);
    }
}