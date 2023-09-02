<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;
use Sotbit\RestAPI\Config\Config;
use Sotbit\RestAPI\Exception\AuthException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Sotbit\RestAPI\Localisation as l;

/**
 * Class Base
 *
 * @package Sotbit\RestAPI\Middleware
 * @author Andrey Sapronov <a.sapronov@sotbit.ru>
 */
abstract class Base
{
    /**
     * @param  string  $jwtHeader
     *
     * @return object
     * @throws AuthException
     * @throws \Firebase\JWT\BeforeValidException
     * @throws \Firebase\JWT\ExpiredException
     * @throws \Firebase\JWT\SignatureInvalidException
     */
    protected function decodedHeader(string $jwtHeader)
    {
        if(!$jwtHeader) { 
            throw new AuthException(l::get('ERROR_AUTH'), 401);
        }
        $jwt = explode('Bearer ', $jwtHeader);
        if(!isset($jwt[1])) {
            throw new AuthException(l::get('ERROR_AUTH_TOKEN_INVALID'), 401);
        }

        return $this->checkToken($jwt[1]);
    }

    /**
     * @param  string  $token
     *
     * @return object
     * @throws AuthException
     * @throws \Firebase\JWT\BeforeValidException
     * @throws \Firebase\JWT\ExpiredException
     * @throws \Firebase\JWT\SignatureInvalidException
     */
    protected function checkToken(string $token)
    {
        try {
            $decoded = JWT::decode($token, new Key(Config::getInstance()->getInstance()->getSecretKey(), 'HS512'));
            if(is_object($decoded) && isset($decoded->sub)) {
                return $decoded;
            }
            throw new AuthException(l::get('ERROR_AUTH_TOKEN_REQUIRED'), 403);
        } catch(\UnexpectedValueException $exception) {
            throw new AuthException(l::get('ERROR_AUTH_TOKEN_REQUIRED'), 403);
        } catch(\DomainException $exception) {
            throw new AuthException(l::get('ERROR_AUTH_TOKEN_REQUIRED'), 403);
        }
    }

    /**
     * Get user id from request header
     *
     * @param  Request  $request
     *
     * @return int
     */
    public function getUserId(Request $request): int
    {
        $userId = 0;
        $jwtHeader = $request->getHeaderLine('Authorization');

        if($jwtHeader) {
            try {
                $decoded = $this->decodedHeader($jwtHeader);
                if(is_object($decoded) && $decoded->sub !== null) {
                    $userId = (int)$decoded->sub;
                }
            } catch(AuthException $e) {
            }
        }

        return $userId;
    }

    
    /**
     * @param  string  $token
     *
     * @return object
     * @throws AuthException
     * @throws \Firebase\JWT\BeforeValidException
     * @throws \Firebase\JWT\ExpiredException
     * @throws \Firebase\JWT\SignatureInvalidException
     */
    protected function findTokenInCookie($request)
    {  
        $cookies = $request->getCookieParams();
        if (isset($cookies["BITRIX_SM_API_TOKEN"])) { 
            return $cookies["BITRIX_SM_API_TOKEN"]; 
        } else {
            return NULL; 
        }


        // $cookies = $request->getCookieParams();
        //  foreach ($cookies as $cookie => $value) {
        //      if ('Rememberme' === $cookie) {
        //          $tockenAccess = $value;
        //          break;
        //      }
        //  }
        //  /*
        //  $cookies = explode(';', $request->getHeaders()['HTTP_COOKIE'][0]);
        //  foreach($cookies As $cookie)
        //  {
        //      if(trim(stristr($cookie, '=', true)) === 'Rememberme') {
        //          $tockenAccess = trim(substr(stristr($cookie, '=', false), 1));
        //          break;
        //      }
        //  }
        //  */
        //  if (false !== $tockenAccess) {
        //      $security = new SecurityController();
        //      return $security->tockenAuthenticate($tockenAccess);
        //  }


    }

}
