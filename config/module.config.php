<?php
/**
 * Created by PhpStorm.
 * User: semihs
 * Date: 24.10.14
 * Time: 13:50
 */

return array(
    'service_manager'    => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases'            => array(
            'translator' => 'MvcTranslator',
        ),
        'factories'          => array(
            'YoutubeDownload'     => function ($sm) {
                return new \Youtube\Downloader\Download();
            },
        )
    ),
    'videoInfoUrl' => 'http://www.youtube.com/get_video_info?el=detailpage&ps=default&eurl=&gl=US&hl=en&sts=15888&video_id=',
    'thumbnailUrl' => 'http://i1.ytimg.com/vi/',
    'watchUrl' => 'http://www.youtube.com/watch?v='
);