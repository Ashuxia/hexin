<?php

namespace Ashuxia\Hexin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HexinExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'hexin', __NAMESPACE__.'\Provider'
        );
    }
}
