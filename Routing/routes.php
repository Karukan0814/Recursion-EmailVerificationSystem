<?php

use Exceptions\AuthenticationFailureException;
use Helpers\ValidationHelper;
use Helpers\Authenticate;
use Models\ComputerPart;
use Response\FlashData;
use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Response\Render\RedirectRenderer;
use Database\DataAccess\DAOFactory;
use Helpers\SendVerificationMail;
use Response\Render\JSONRenderer;
use Routing\Route;
use Helpers\ValueType;
use Models\User;
use Response\Render\MediaRenderer;

return [
    'login' => Route::create('login', function (): HTTPRenderer {
        return new HTMLRenderer('page/login');
    })->setMiddleware(['guest']),
    'form/login' => Route::create('form/login', function (): HTTPRenderer {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method!');

            $required_fields = [
                'email' => ValueType::EMAIL,
                'password' => ValueType::STRING,
            ];

            $validatedData = ValidationHelper::validateFields($required_fields, $_POST);

            $userInfo=Authenticate::authenticate($validatedData['email'], $validatedData['password']);
            
            Authenticate::loginAsUser($userInfo);
            FlashData::setFlashData('success', 'Logged in successfully.');
            return new RedirectRenderer('random/part');
        } catch (AuthenticationFailureException $e) {
            error_log($e->getMessage());

            FlashData::setFlashData('error', 'Failed to login, wrong email and/or password.');
            return new RedirectRenderer('login');
        } catch (InvalidArgumentException $e) {
            error_log($e->getMessage());

            FlashData::setFlashData('error', 'Invalid Data.');
            return new RedirectRenderer('login');
        } catch (Exception $e) {
            error_log($e->getMessage());

            FlashData::setFlashData('error', 'An error occurred.');
            return new RedirectRenderer('login');
        }
    })->setMiddleware(['guest']),
    'register' => Route::create('register', function (): HTTPRenderer {
        return new HTMLRenderer('page/register');
    })->setMiddleware(['guest']),
    'form/register' => Route::create('form/register', function (): HTTPRenderer {
        try {
            // リクエストメソッドがPOSTかどうかをチェックします
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method!');

            $required_fields = [
                'username' => ValueType::STRING,
                'email' => ValueType::EMAIL,
                'password' => ValueType::PASSWORD,
                'confirm_password' => ValueType::PASSWORD,
                'company' => ValueType::STRING,
            ];

            $userDao = DAOFactory::getUserDAO();

            // シンプルな検証
            $validatedData = ValidationHelper::validateFields($required_fields, $_POST);

            if ($validatedData['confirm_password'] !== $validatedData['password']) {
                FlashData::setFlashData('error', 'Invalid Password!');
                return new RedirectRenderer('register');
            }

            // Eメールは一意でなければならないので、Eメールがすでに使用されていないか確認します
            if ($userDao->getByEmail($validatedData['email'])) {
                FlashData::setFlashData('error', 'Email is already in use!');
                return new RedirectRenderer('register');
            }

            // 新しいUserオブジェクトを作成します
            $user = new User(
                username: $validatedData['username'],
                email: $validatedData['email'],
                company: $validatedData['company']
            );

            // データベースにユーザーを作成しようとします
            $success = $userDao->create($user, $validatedData['password']);

            if (!$success) throw new Exception('Failed to create new user!');


            // ユーザーが登録する際、自動的に署名付き検証 URL を生成し、ユーザーのメールアドレスに送信します。

            // 登録したユーザー情報を取得
            $userInfo = $userDao->getByEmail($validatedData['email']);

            if (!$userInfo) throw new Exception('Failed to get the registered user info!');

            // 生成された署名付き URL は、id、user、expiration のクエリパラメータを URL 内にカプセル化する必要があります。
            $queryParameters = [];
            $queryParameters['id'] = $userInfo->getId();
            $queryParameters['user'] = $userInfo->getUsername();
            $queryParameters['expiration'] = time() + (30 * 60); //現在時刻＋３０分後をexpire時刻にする
            // $queryParameters['expiration'] = time() + (10); //test 10s


            // 例：https://yourdomain.com/verify/email?id=434554&user=179e9c6498071768e9c6dcb606be681b35ec39d7c1cd462af5eee998793de96a&expiration=1686451200&signature=dc6f3568745f317e0227956332b7845187a8f6b6b46f1b21e533957454cd11d9
            $verificationUrl = Route::create('verify/email', function () {
            })->getSignedURL($queryParameters);


            // PHPMailer を使用して現在のユーザーに検証メールを送信する sendVerificationEmail 関数を実装します。
            SendVerificationMail::sendVerification($validatedData['email'], $queryParameters['user'], $verificationUrl);


            // ユーザーログイン
            Authenticate::loginAsUser($user);

            FlashData::setFlashData('success', 'verification mail was sent to your mail.');
            return new RedirectRenderer('register');
        } catch (\InvalidArgumentException $e) {
            error_log($e->getMessage());

            FlashData::setFlashData('error', 'Invalid Data.');
            return new RedirectRenderer('register');
        } catch (Exception $e) {
            error_log($e->getMessage());

            FlashData::setFlashData('error', 'An error occurred.');
            return new RedirectRenderer('register');
        }
    })->setMiddleware(['guest']),
    'logout' => Route::create('logout', function (): HTTPRenderer {
        Authenticate::logoutUser();
        FlashData::setFlashData('success', 'Logged out.');
        return new RedirectRenderer('login');
    })->setMiddleware(['auth']),
    'random/part' => Route::create('random/part', function (): HTTPRenderer {
        $partDao = DAOFactory::getComputerPartDAO();
        $part = $partDao->getRandom();
        if($part === null) throw new Exception('No parts are available!');
        // $part = null;
        return new HTMLRenderer('component/computer-part-card', ['part' => $part]);
    })->setMiddleware(['auth']),
    'parts' => Route::create('parts', function (): HTTPRenderer {
        // IDの検証
        $id = ValidationHelper::integer($_GET['id'] ?? null);

        $partDao = DAOFactory::getComputerPartDAO();
        $part = $partDao->getById($id);

        if ($part === null) throw new Exception('Specified part was not found!');

        return new HTMLRenderer('component/computer-part-card', ['part' => $part]);
    }),
    'update/part' => Route::create('update/part', function (): HTTPRenderer {
        $user = Authenticate::getAuthenticatedUser();
        $part = null;
        $partDao = DAOFactory::getComputerPartDAO();
        if (isset($_GET['id'])) {
            $id = ValidationHelper::integer($_GET['id']);
            $part = $partDao->getById($id);
            if ($user->getId() !== $part->getSubmittedById()) {
                FlashData::setFlashData('error', 'Only the author can edit this computer part.');
                return new RedirectRenderer('register');
            }
        }
        return new HTMLRenderer('component/update-computer-part', ['part' => $part]);
    })->setMiddleware(['auth']),
    'form/update/part' => Route::create('form/update/part', function (): HTTPRenderer {
        try {
            // クエストメソッドがPOSTかどうかをチェックします
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method!');
            }

            $required_fields = [
                'name' => ValueType::STRING,
                'type' => ValueType::STRING,
                'brand' => ValueType::STRING,
                'modelNumber' => ValueType::STRING,
                'releaseDate' => ValueType::DATE,
                'description' => ValueType::STRING,
                'performanceScore' => ValueType::INT,
                'marketPrice' => ValueType::FLOAT,
                'rsm' => ValueType::FLOAT,
                'powerConsumptionW' => ValueType::FLOAT,
                'lengthM' => ValueType::FLOAT,
                'widthM' => ValueType::FLOAT,
                'heightM' => ValueType::FLOAT,
                'lifespan' => ValueType::INT,
            ];

            $partDao = DAOFactory::getComputerPartDAO();

            // 入力に対する単純な認証。実際のシナリオでは、要件を満たす完全な認証が必要になることがあります
            $validatedData = ValidationHelper::validateFields($required_fields, $_POST);

            $user = Authenticate::getAuthenticatedUser();

            // idが設定されている場合は、認証を行います
            if (isset($_POST['id'])) {
                $validatedData['id'] = ValidationHelper::integer($_POST['id']);
                $currentPart = $partDao->getById($_POST['id']);
                if ($currentPart === null || $user->getId() !== $currentPart->getSubmittedById()) {
                    return new JSONRenderer(['status' => 'error', 'message' => 'Invalid Data Permissions!']);
                }
            }

            $validatedData['submitted_by_id'] = $user->getId();

            $part = new ComputerPart(...$validatedData);

            error_log(json_encode($part->toArray(), JSON_PRETTY_PRINT));

            // 新しい部品情報でデータベースの更新を試みます。
            // 別の方法として、createOrUpdateを実行することもできます。
            if (isset($validatedData['id'])) $success = $partDao->update($part);
            else $success = $partDao->create($part);

            if (!$success) {
                throw new Exception('Database update failed!');
            }

            return new JSONRenderer(['status' => 'success', 'message' => 'Part updated successfully', 'id' => $part->getId()]);
        } catch (\InvalidArgumentException $e) {
            error_log($e->getMessage());
            return new JSONRenderer(['status' => 'error', 'message' => 'Invalid data.']);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(['status' => 'error', 'message' => 'An error occurred.']);
        }
    })->setMiddleware(['auth']),
    'test/share/files/jpg' => Route::create('test/share/files/jpg', function (): HTTPRenderer {
        // このURLは署名を必要とするため、URLが正しい署名を持つ場合にのみ、この最終ルートコードに到達します。
        $required_fields = [
            'user' => ValueType::STRING,
            'filename' => ValueType::STRING, // 本番環境では、有効なファイルパスに対してバリデーションを行いますが、ファイルパスの単純な文字列チェックを行います。
        ];

        $validatedData = ValidationHelper::validateFields($required_fields, $_GET);

        $part = new MediaRenderer(sprintf("images/shared/%s/%s", $validatedData['user'], $validatedData['filename']), 'jpg');
        return $part;
        // return new MediaRenderer(sprintf("images/shared/%s/%s", $validatedData['user'],$validatedData['filename']), 'jpg');
        // return new HTMLRenderer('component/computer-part-card', ['part'=>$part->getFileName()]);

    })->setMiddleware(['signature']),
    'test/share/files/jpg/generate-url' => Route::create('test/share/files/jpg/generate-url', function (): HTTPRenderer {
        $required_fields = [
            'user' => ValueType::STRING,
            'filename' => ValueType::STRING,
        ];

        $validatedData = ValidationHelper::validateFields($required_fields, $_GET);

        if (isset($_GET['lasts'])) {
            $validatedData['expiration'] = time() + ValidationHelper::integer($_GET['lasts']);
        }

        return new JSONRenderer(['url' => Route::create('test/share/files/jpg', function () {
        })->getSignedURL($validatedData)]);
    }),
    'verify/email' => Route::create('verify/email', function (): HTTPRenderer {

        error_log("verify/email _start");

        // GET ルート /verify/email を実装し、id、user、expiration、signature を受け入れます。
        $required_fields = [
            'id' => ValueType::INT,
            'user' => ValueType::STRING,
            'expiration' => ValueType::INT,
            'signature' => ValueType::STRING,

        ];
        $validatedData = ValidationHelper::validateFields($required_fields, $_GET);

        $id = $validatedData['id'];
        $hashedUser = $validatedData['user'];
        $expiration = $validatedData['expiration'];
        $signature = $validatedData['signature'];

        // ユーザーDAO作成
        $userDao = DAOFactory::getUserDAO();

        // ルート内で、ユーザーの詳細が URL パラメータと一致していることを確認します。
        $userInfo = $userDao->getById($id);
        if ($hashedUser !== $userInfo->getUsername()) {
            throw new Exception('inconsistency in user information');
        }

        // 期限切れでないか確認
        if (time() > $expiration) {
            // 期限切れの場合は'Send Verification Email' ページにリダイレクト
            error_log("verify/email _expire");
            FlashData::setFlashData('error', 'This link is expired.');

            return new RedirectRenderer('verify/resend');

            // return new HTMLRenderer('component/resend-verification', ['userInfo' => $userInfo]);
        }


        // ルート内で、データベースの email_verified 列を更新します。
        $confirmResult = $userDao->confirmEmail($userInfo);
        if (!$confirmResult) {
            throw new Exception('Something wrong with confirming your email. Please contact admin.');
        }

        // ログイン処理を行う
        $verifiedUser = $userDao->getById($id);
        Authenticate::loginAsUser($verifiedUser);

        error_log("verify/email _confirm");
        FlashData::setFlashData('success', 'your mail address was verified.');
        return new RedirectRenderer('random/part');


        //  ミドルウェアを介して URL 署名をチェックします。
        // ミドルウェアを通してリンクが期限切れでないことを確認します。
        // 'signature'を指定することで、ミドルウェアのSignatureValidationMiddlewareを呼び出している。
        // これは URLに有効な署名があるか、期限切れでないかを確認している
    })->setMiddleware(['signature']),
    'verify/resend' => Route::create('login', function (): HTTPRenderer {

        return new HTMLRenderer('component/resend-verification', ['userInfo' => Authenticate::getAuthenticatedUser()]);
    }),
    'verify/form/resend' => Route::create('verify/resend', function (): HTTPRenderer {

        error_log("verify/resend");
        // リクエストメソッドがPOSTかどうかをチェックします
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method!');

        $required_fields = [
            'id' => ValueType::INT,
            'email' => ValueType::EMAIL,
        ];
        $validatedData = ValidationHelper::validateFields($required_fields, $_POST);

        $userDao = DAOFactory::getUserDAO();

        error_log($validatedData['id']);
        // 登録したユーザー情報を取得
        $userInfo = $userDao->getById($validatedData['id']);

        if (!$userInfo) throw new Exception('No registered user info.');


        //登録されたユーザー情報のメールアドレスとフォームから取得したアドレスが違うとき
        // つまり、resendする先のメールアドレスを変えたいとき
        if ($validatedData["email"] !== $userInfo->getEmail()) {
            // ユーザー情報のメールアドレスを更新する。
            $userInfo->setEmail($validatedData["email"]);
            $updateResult = $userDao->update($userInfo);
            if (!$updateResult) throw new Exception('Something wrong with updating email address');
        }


        // 生成された署名付き URL は、id、user、expiration のクエリパラメータを URL 内にカプセル化する必要があります。
        $queryParameters = [];
        $queryParameters['id'] = $userInfo->getId();
        $queryParameters['user'] = $userInfo->getUsername();
        $queryParameters['expiration'] = time() + (30 * 60); //現在時刻＋３０分後をexpire時刻にする

        // 例：https://yourdomain.com/verify/email?id=434554&user=179e9c6498071768e9c6dcb606be681b35ec39d7c1cd462af5eee998793de96a&expiration=1686451200&signature=dc6f3568745f317e0227956332b7845187a8f6b6b46f1b21e533957454cd11d9
        $verificationUrl = Route::create('verify/email', function () {
        })->getSignedURL($queryParameters);


        // PHPMailer を使用して現在のユーザーに検証メールを送信する sendVerificationEmail 関数を実装します。
        SendVerificationMail::sendVerification($validatedData['email'], $queryParameters['user'], $verificationUrl);

        FlashData::setFlashData('success', 'verification mail was sent to your mail.');

        return new RedirectRenderer('login');

    })->setMiddleware(['guest']),
];
