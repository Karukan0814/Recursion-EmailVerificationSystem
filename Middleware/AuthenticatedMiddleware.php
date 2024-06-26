<?php

namespace Middleware;

use Helpers\Authenticate;
use Response\FlashData;
use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Response\Render\RedirectRenderer;

class AuthenticatedMiddleware implements Middleware
{
    public function handle(callable $next): HTTPRenderer
    {
        error_log('Running authentication check...');
        if(!Authenticate::isLoggedIn()){
            FlashData::setFlashData('error', 'Must login to view this page.');
            return new RedirectRenderer('login');
        }else{
            if(!Authenticate::isConfirmed()){
                FlashData::setFlashData('error', 'Your email address has not been verified yet.');
                return new RedirectRenderer('verify/resend');
    
                // return new HTMLRenderer('component/resend-verification', ['userInfo' => Authenticate::getAuthenticatedUser()]);
            }
        }
        

        return $next();
    }
}