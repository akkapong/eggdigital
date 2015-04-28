<?php

return array(

    'SYSLOG' => array(

        'EMERGENCY' => array(
            'CODE'    => 0,
            'KEYWORD' => 'EMERGENCY',
        ),

        'ALERT' => array(
            'CODE'    => 1,
            'KEYWORD' => 'ALERT',
        ),

        'CRITICAL' =>array(
            'CODE'    => 2,
            'KEYWORD' => 'CRIT',
        ),

        'ERROR' => array(
            'CODE'    => 3,
            'KEYWORD' => 'ERROR',
        ),

        'WARNING' => array(
            'CODE'    => 4,
            'KEYWORD' => 'WARNING',
        ),

        'NOTICE' => array(
            'CODE'    => 5,
            'KEYWORD' => 'NOTICE',
        ),

        'INFORMATIONAL' => array(
            'CODE'    => 6,
            'KEYWORD' => 'INFO',
        ),

        'DEBUG' => array(
            'CODE'    => 6,
            'KEYWORD' => 'DEBUG',
        ),
    ),

    'ENVIRONMENT'      => 'alpha',
    'LOG_PATH'         => '/var/projects/logs/api/alpha/',
    'cdn_siammakroapp' => 'http://staging-cdn.siammakroapp.com',
    'CDN_PATH'         => '/data/cdn/',
    'EXPIRE_TIMESTAMP' => 60,
);
