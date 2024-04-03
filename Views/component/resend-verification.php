<div class="container mt-10">

    <div class="col-12 d-flex flex-column align-items-center"">
    <p>Click the button below to resend a verification mail.</p>
    
        
        <form action="form/resend" method="post">
            <input type="hidden" name="csrf_token" value="<?= Helpers\CrossSiteForgeryProtection::getToken(); ?>">
            <input type="hidden" name="id" value="<?= $userInfo->getId()?>">
            
            <input type="input" name="email" value="<?= $userInfo->getEmail() ?>">
            
            <button type="submit" class="btn btn-primary">Resend</button>
        </form>
    </div>
</div>

<script src="/js/app.js"></script>