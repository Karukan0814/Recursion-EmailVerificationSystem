<?php

namespace Middleware;

use Helpers\Authenticate;
use Response\HTTPRenderer;
use Response\Render\RedirectRenderer;

class GuestMiddleware implements Middleware
{
    public function handle(callable $next): HTTPRenderer
    {
        error_log('Running authentication check...');
        // ユーザーがログインしている+既にconfirm済みの場合は、メッセージなしでランダムパーツのページにリダイレクトします
        if(Authenticate::isLoggedIn()){
            error_log("is login true");
            return new RedirectRenderer('random/part');
        }
        error_log("is login false");

        return $next();
    }
}