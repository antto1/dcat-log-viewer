<?php

namespace Antto\LogViewer;

use Dcat\Admin\Extend\ServiceProvider;

class DcatLogViewerServiceProvider extends ServiceProvider
{
    // protected $js = [
    // 	'js/index.js',
    // ];
    // protected $css = [
    // 	'css/index.css',
    // ];

    // 定义菜单
    protected $menu = [
        [
            'title' => "日志记录",
            'uri'   => 'log-viewer',
            'icon'  => 'fa-server',
        ],
    ];

    public function register()
    {
        //
    }

    public function init()
    {
        parent::init();
    }
}
