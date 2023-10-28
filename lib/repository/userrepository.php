<?php

declare(strict_types=1);

namespace Sotbit\RestAPI\Repository;

use Slim\Http\StatusCode;
use Slim\Http\UploadedFile;
use Sotbit\RestAPI\Exception\UserException;
use Sotbit\RestAPI\Core;
use Bitrix\Main\UserTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Type;
use Bitrix\Main\Config\Option;
use Sotbit\RestAPI\Core\Helper;
use Sotbit\RestAPI\Localisation as l;


class UserRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $allowedUserFields
    = [
        'main'     => [
            //"ID",
            "LOGIN",
            //"ACTIVE",
            "EMAIL",
            "NAME",
            "LAST_NAME",
            "SECOND_NAME",
            "PERSONAL_PHOTO",
            "LAST_LOGIN",
            "DATE_REGISTER",
        ],
        'groups'   => [],
        'personal' => [
            "PERSONAL_GENDER",
            "PERSONAL_PROFESSION",
            "PERSONAL_WWW",
            "PERSONAL_BIRTHDAY",
            "PERSONAL_PHONE",
            "PERSONAL_FAX",
            "PERSONAL_MOBILE",
            "PERSONAL_STREET",
            "PERSONAL_CITY",
            "PERSONAL_STATE",
            "PERSONAL_ZIP",
            "PERSONAL_COUNTRY",
            "PERSONAL_NOTES",
            "PERSONAL_MAILBOX",
        ],
        'work'      => [
            "WORK_COMPANY",
            "WORK_DEPARTMENT",
            "WORK_POSITION",
            "WORK_WWW",
            "WORK_PROFILE",
            "WORK_LOGO",
            "WORK_PHONE",
            "WORK_FAX",
            "WORK_NOTES",
            "WORK_COUNTRY",
            "WORK_STATE",
            "WORK_CITY",
            "WORK_ZIP",
            "WORK_STREET",
            "WORK_MAILBOX",
            //"UF_DEPARTMENT", "UF_INTERESTS", "UF_SKILLS", "UF_WEB_SITES", "UF_XING", "UF_LINKEDIN", "UF_FACEBOOK", "UF_TWITTER", "UF_SKYPE", "UF_DISTRICT", "UF_PHONE_INNER"
        ],
    ];

    /**
     * @return string
     * @throws \Bitrix\Main\LoaderException
     */
    public function getUserClass()
    {
        if (Loader::includeModule('intranet')) {
            return '\Bitrix\Intranet\UserTable';
        }
        return '\Bitrix\Main\UserTable';
    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    public function getList($params): array
    {
        $result = [];
        $params = $this->prepareNavigationBase($params);

        // User table
        $getListClassName = $this->getUserClass();
        $defSelect = Helper::arrayValueRecursive($this->allowedUserFields);

        // Filter
        $filter = array_merge([/*'=IS_REAL_USER' => 'Y',*/'ACTIVE' => 'Y'], $params['filter']);

        $select = array_intersect($params['select'], $defSelect);
        $select = count($select) ? array_unique(array_merge($select, ['ID'])) : $defSelect;

        $filterParams = [
            'select' => $select,
            'filter' => $filter,
            'order'  => $params['order'],
            'limit'  => $params['limit'],
            'offset' => ($params['limit'] * ($params['page'] - 1)),
            'data_doubling' => false,
        ];

        $dbRes = $getListClassName::getList($filterParams);
        while ($res = $dbRes->fetch()) {
            $result[$res['ID']] = $res;
        }

        return $result;
    }

    /**
     * Get user info
     *
     * @param  int  $userId
     *
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    public function get(int $userId): array
    {
        $result = [];

        $res = UserTable::getById($userId);

        if (!($user = $res->fetch())) {
            throw new UserException(l::get('ERROR_USER_NOT_FOUND'), 404);
        }

        // $user = reset($user);

        // Personal photo
        if ($user['PERSONAL_PHOTO']) {
            $user['PERSONAL_PHOTO'] = \CFile::GetPath($user['PERSONAL_PHOTO']);
        } else {
            $user['PERSONAL_PHOTO'] = 'images/nophoto.jpg';
        }

        // // User country
        // $user['PERSONAL_COUNTRY'] = $user['PERSONAL_COUNTRY'] ? GetCountryByID((int)$user['PERSONAL_COUNTRY']) : null;

        // User city
        $user['PERSONAL_CITY'] = $user['PERSONAL_CITY'] ? $user['PERSONAL_CITY'] : null;

        // Birthday format
        if ($user['PERSONAL_BIRTHDAY'] && $user['PERSONAL_BIRTHDAY'] instanceof Type\Date) {
            $user['PERSONAL_BIRTHDAY'] = $user['PERSONAL_BIRTHDAY']->format(
                Type\Date::convertFormatToPhp(\CSite::GetDateFormat('SHORT'))
            );
        }

        // Gender format
        if ($user['PERSONAL_GENDER']) {
            $user['PERSONAL_GENDER'] = $user['PERSONAL_GENDER'] === 'M' ? l::get('USER_MALE') : l::get('USER_FEMALE');
        } else {
            $user['PERSONAL_GENDER'] = l::get('USER_DONT_KNOW');
        }


        // Get groups
        $getListClassName = $this->getUserClass();
        $groups = $getListClassName::getUserGroupIds($user['ID']);

        foreach ($this->allowedUserFields as $key => $val) {
            if ($key === 'groups') {
                $result[$key] = $groups;
            } else {
                $result[$key] = array_intersect_key($user, array_flip(array_diff($val, [''])));
            }
        }




        // add title for values
        $emptySkip = true;
        $reformatResult = [];
        foreach ($result as $nameTab => $values) {
            if ($nameTab === 'groups') {
                $reformatResult[$nameTab] = $values;
                continue;
            }
            $valuesTab = [];
            foreach ($values as $valueName => $value) {
                if ($emptySkip && !$value) {
                    continue;
                }
                $valuesTab[$valueName]['TITLE'] = l::get('USER_' . $valueName);
                $valuesTab[$valueName]['VALUE'] = $value;
            }
            $reformatResult[$nameTab]['TITLE'] = l::get('USER_TITLE_' . $nameTab);
            $reformatResult[$nameTab]['VALUES'] = $valuesTab;
        }

        return $reformatResult;
    }




    /**
     * @param  int  $id
     *
     * @return string
     * @throws UserException
     * @throws \Bitrix\Main\LoaderException
     */
    public function getUserSid(int $id): string
    {
        $userInfo = $this->checkUserById($id);

        return $userInfo['EMAIL'] ?: $userInfo['LOGIN'];
    }




    /**
     * @param  int  $id
     *
     * @return string|null
     * @throws UserException
     * @throws \Bitrix\Main\LoaderException
     */
    public function getUserAvatarSrc(int $id)
    {
        $photo = '';
        $user = $this->getList([
            'select'        => ['PERSONAL_PHOTO'],
            'filter'        => ['=ID' => $id],
            'limit'         => 1,
        ]);

        if (!$user) {
            throw new UserException(l::get('ERROR_USER_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        }

        $user = reset($user);

        // Personal photo
        if (!empty($user['PERSONAL_PHOTO'])) {
            $photo = \CFile::GetPath($user['PERSONAL_PHOTO']);
        }

        return $photo;
    }




    /**
     * Sign up
     *
     * @param  string  $login
     * @param  string  $password
     *
     * @return array
     */
    public function login(string $login, string $password): array
    {
        $returnFields = ['ID', 'LOGIN'];
        /*$select = [
            'ID',
            'LOGIN',
            'PASSWORD',
            'ACTIVE',
        ];
        $user = UserTable::getList(['select' => $select, 'filter' => ['=LOGIN' => $login], 'limit' => 1])->fetchRaw();*/

        $user = \CUser::GetByLogin($login)->fetch();

        if (empty($user) || !$this->isUserPassword($user['PASSWORD'], $password)) {
            throw new UserException(l::get('ERROR_AUTH_INCORRECT'), StatusCode::HTTP_BAD_REQUEST);
        }

        if ($user['ACTIVE'] !== 'Y') {
            throw new UserException(l::get('ERROR_AUTH_USER_DEACTIVATED'), StatusCode::HTTP_BAD_REQUEST);
        }


        return array_intersect_key($user, array_flip($returnFields));
    }



    /**
     * @param  string  $email
     *
     * @return array
     * @throws UserException
     * @throws \Bitrix\Main\LoaderException
     */
    public function forgot(string $email): array
    {
        $user = $this->checkUserByEmail($email);

        return \CUser::SendPassword($user['LOGIN'], $user['EMAIL']);
    }





    /**
     * Check user by email
     *
     * @param  string  $email
     *
     * @return array
     * @throws UserException
     * @throws \Bitrix\Main\LoaderException
     */
    public function checkUserByEmail(string $email): array
    {
        $user = $this->getList([
            'select'        => ['ID', 'LOGIN', 'EMAIL'],
            'filter'        => ['=EMAIL' => $email],
            'limit'         => 1,
        ]);

        if (!$user) {
            throw new UserException(l::get('ERROR_USER_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        }

        return reset($user);
    }




    /**
     * Check user by login
     *
     * @param  string  $login
     *
     * @return array
     * @throws UserException
     * @throws \Bitrix\Main\LoaderException
     */
    public function checkUserByLogin(string $login): array
    {
        $user = $this->getList([
            'select'        => ['ID', 'LOGIN', 'EMAIL'],
            'filter'        => ['=LOGIN' => $login],
            'limit'         => 1,
        ]);

        if (!$user) {
            throw new UserException(l::get('ERROR_USER_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        }

        return reset($user);
    }



    /**
     * Check user by Id
     *
     * @param  int  $id
     *
     * @return array
     * @throws UserException
     * @throws \Bitrix\Main\LoaderException
     */
    public function checkUserById(int $id): array
    {
        $user = $this->getList([
            'select'        => ['ID', 'LOGIN', 'EMAIL'],
            'filter'        => ['=ID' => $id],
            'limit'         => 1,
        ]);

        if (!$user) {
            throw new UserException(l::get('ERROR_USER_NOT_FOUND'), StatusCode::HTTP_NOT_FOUND);
        }

        return reset($user);
    }




    /**
     * @param  string  $userPassword
     * @param  string  $inputPassword
     *
     * @return bool
     */
    public function isUserPassword(string $userPassword, string $inputPassword): bool
    {
        // v20.5.400
        // 2020-07-24
        if (class_exists('\Bitrix\Main\Security\Password')) {
            return \Bitrix\Main\Security\Password::equals($userPassword, $inputPassword);
        }

        // Old
        $salt = substr($userPassword, 0, (strlen($userPassword) - 32));
        $realPassword = substr($userPassword, -32);
        $inputPassword = md5($salt . $inputPassword);

        return ($inputPassword == $realPassword);
    }



    public function updateUser(array $data, int $userId): string
    {
        // check permission
        //$this->permission->user($userId)->section($iblockId, 0);

        $uploaded_files = $this->checkFiles($data['uploaded_files']);

        $data['files'] = \CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"] . $uploaded_files[0]['file_path']);
        $data['files']['del'] = "Y";            
        $data['files']['old_file'] = $this->getUserPhoto((int)$userId); 

        $fields = array(
            "NAME"  => $data["name"],
            "EMAIL" => $data["email"],
            "PERSONAL_PHONE" => $data["phone"],
            "PERSONAL_CITY" => $data["town"],
            'PERSONAL_PHOTO' => $data['files'],
        );

        $user = new \CUser;

        if ($user->Update($userId, $fields)) {
            $message = 'Данные обновлены';
        } else {
            $message = $user->LAST_ERROR;
            throw new UserException($message, StatusCode::HTTP_BAD_REQUEST);
        }

        $result = $message;

        return $result;
    }




    /**
     * Check and collect uploaded files
     *
     * @param $files
     *
     * @return array
     * @throws UserException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function checkFiles($files)
    {
        $returnFiles = [];
        $maxSize = (int)Option::get("support", "SUPPORT_MAX_FILESIZE") * 1024;
        // $directory = $this->get('upload_directory');
        $directory = './../uploads/user_avatars/'.date("Ymd");
        $files = $files['photo_file'];

        if ($files) {
            if (!is_dir($directory)){   
                mkdir($directory, 0777);
            } 
            // check and collect files into an array
            if (is_array($files)) {
                foreach ($files as $uploadFile) {


                    if ($uploadFile instanceof UploadedFile) {
                        if ($uploadFile->getError() === UPLOAD_ERR_OK) {

                            $returnFiles[] = [
                                'name'      => Core\Helper::convertEncodingToSite($uploadFile->getClientFilename()),
                                'type'      => $uploadFile->getClientMediaType(),
                                'tmp_name'  => $uploadFile->file,
                                'error'     => $uploadFile->getError(),
                                'size'      => $uploadFile->getSize(),
                                "MODULE_ID" => "user",
                                "file_path" => '/uploads/user_avatars/'. date("Ymd") . '/'. $this->moveUploadedFile($directory, $uploadFile),
                            ];
                        } else {
                            throw new UserException($uploadFile->getError(), StatusCode::HTTP_BAD_REQUEST);
                        }
                    }
                }
            } else {
                if ($files instanceof \Slim\Http\UploadedFile) {
                    if ($files->getError() === UPLOAD_ERR_OK) {

                        $returnFiles[] = [
                            'name'      => Core\Helper::convertEncodingToSite($files->getClientFilename()),
                            'type'      => $files->getClientMediaType(),
                            'tmp_name'  => $files->file,
                            'error'     => $files->getError(),
                            'size'      => $files->getSize(),
                            "MODULE_ID" => "user",
                            "file_path" => '/uploads/user_avatars/'. date("Ymd") . '/'. $this->moveUploadedFile($directory, $files),
                        ];
                    } else {
                        throw new UserException($files->getError(), StatusCode::HTTP_BAD_REQUEST);
                    }
                }
            }

            // check filesize
            if ($maxSize > 0 && count($returnFiles) > 0) {
                foreach ($returnFiles as $returnFile) {
                    if ((int)$returnFile['size'] > $maxSize) {
                        throw new UserException(
                            l::get(
                                'ERROR_SUPPORT_FILE',
                                [
                                    '#FILE#' => $returnFile['name'],
                                    '#SIZE#' => round($maxSize / 1024 / 1024, 1),
                                ]
                            ),
                            StatusCode::HTTP_BAD_REQUEST
                        );
                    }
                }
            }
        }

        return $returnFiles;
    }



    public function getUserPhoto(int $id)
    {
        $src = null;
        if ($id) {
            $src = $this->getUserAvatarSrc($id);
            $src = empty($src) ? null : $src;
        }

        return $src;
    }



    function moveUploadedFile(string $directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);

        // see http://php.net/manual/en/function.random-bytes.php
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }
}
