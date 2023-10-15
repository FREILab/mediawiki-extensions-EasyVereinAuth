<?php

namespace MediaWiki\Extension\EasyVereinAuth;

/**
 * Class EasyVereinAuthHooks
 * @author  Tobias Bodewig
 * @package MediaWiki
 * @subpackage EasyVereinAuth
 */
class EasyVereinAuthHooks {

    /**
     * Extension registration callback
     */
    public static function onRegistration()
    {
        $GLOBALS['wgPluggableAuth_Config'] = [
            "login" => [
                'plugin' => 'EasyVereinAuth',
                'buttonLabelMessage' => 'easyvereinauth-loginbtn-text',
            ]
        ];
    }
}
