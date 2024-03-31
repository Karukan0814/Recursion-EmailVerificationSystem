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
        // ユーザーがログインしている場合は、メッセージなしでランダムパーツのページにリダイレクトします
        if(Authenticate::isLoggedIn()){
            return new RedirectRenderer('random/part');
        }

        return $next();
    }
}