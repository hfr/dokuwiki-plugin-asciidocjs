<?php

//use dokuwiki\Extension\AdminPlugin;
use dokuwiki\Extension\RemotePlugin;
use dokuwiki\Remote\RemoteException;

class remote_plugin_asciidocjs extends RemotePlugin
{
    /**
     * Returns details about the remote plugin methods
     *
     * @return array Information about all provided methods. {@see dokuwiki\Remote\RemoteAPI}
     */
    public function _getMethods()
    {
        return [
            'getMediaToken' => [
                'args' => ['string','int','int'],
                'return' => 'string'
            ],
            'test' => [
                'args' => [],
                'return' => 'string'
            ]
        ];
    }

    public function getMediaToken($id, $w, $h)
    {
        return media_get_token($id, $w, $h);
    }
    public function test()
    {
        return 'Test';
    }
}
