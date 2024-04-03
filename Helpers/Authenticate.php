<?php

namespace Helpers;

use Database\DataAccess\DAOFactory;
use Exceptions\AuthenticationFailureException;
use Models\User;

class Authenticate
{
    // 認証されたユーザーの状態をこのクラス変数に保持します
    private static ?User $authenticatedUser = null;
    private const USER_ID_SESSION_KEY = 'user_id';

    public static function loginAsUser(User $user): bool{
        if($user->getId() === null) throw new \Exception('Cannot login a user with no ID.');
        // if(isset($_SESSION[self::USER_ID_SESSION_KEY])) throw new \Exception('User is already logged in. Logout before continuing.');
        if(isset($_SESSION[self::USER_ID_SESSION_KEY])){
            unset($_SESSION[self::USER_ID_SESSION_KEY]);

        }

        $_SESSION[self::USER_ID_SESSION_KEY] = $user->getId();
        error_log($_SESSION[self::USER_ID_SESSION_KEY]);
        return true;
    }

    public static function logoutUser(): bool {
        if (isset($_SESSION[self::USER_ID_SESSION_KEY])) {
            unset($_SESSION[self::USER_ID_SESSION_KEY]);
            self::$authenticatedUser = null;
            return true;
        }
        else throw new \Exception('No user to logout.');
    }

    private static function retrieveAuthenticatedUser(): void{
        if(!isset($_SESSION[self::USER_ID_SESSION_KEY])) return;
        $userDao = DAOFactory::getUserDAO();
        self::$authenticatedUser = $userDao->getById($_SESSION[self::USER_ID_SESSION_KEY]);
    }

    // public static function isConfirmed(): bool{
    //     self::retrieveAuthenticatedUser();

    //     //ユーザー情報が存在する、かつユーザーがconfirm済みか
    //     return self::$authenticatedUser !== null&&self::$authenticatedUser->getConfirmedAt()!==null;
    // }

    public static function isLoggedIn(): bool{
        self::retrieveAuthenticatedUser();

        //ユーザー情報が存在する
        // $result=false;

        // if(self::$authenticatedUser !== null&&self::$authenticatedUser->getConfirmedAt()!==null){
        //     $result=true;
        // }


        return self::$authenticatedUser !== null&&self::$authenticatedUser->getConfirmedAt()!==null;
    }

    public static function getAuthenticatedUser(): ?User{
        self::retrieveAuthenticatedUser();
        return self::$authenticatedUser;
    }

    /**
     * @throws AuthenticationFailureException
     */
    public static function authenticate(string $email, string $password): User{
        $userDAO = DAOFactory::getUserDAO();
        self::$authenticatedUser = $userDAO->getByEmail($email);

        // ユーザーが見つからない場合はnullを返します
        if (self::$authenticatedUser === null) throw new AuthenticationFailureException("Could not retrieve user by specified email %s " . $email);

        // データベースからハッシュ化されたパスワードを取得します
        $hashedPassword = $userDAO->getHashedPasswordById(self::$authenticatedUser->getId());

        if (password_verify($password, $hashedPassword)){
            error_log("password OK");
            self::loginAsUser(self::$authenticatedUser);
            return self::$authenticatedUser;
        }
        else throw new AuthenticationFailureException("Invalid password.");
    }
}