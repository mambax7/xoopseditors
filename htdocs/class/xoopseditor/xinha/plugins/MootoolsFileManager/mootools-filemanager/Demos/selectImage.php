<?php

include('../Backend/FileManager.php');

$browser = new FileManager(
    [
        'directory'     => 'Files/',
        'assetBasePath' => '../Assets',
        'upload'        => false,
        'destroy'       => false,
        'filter'        => 'image/',
    ]
);

$browser->fireEvent(!empty($_GET['event']) ? $_GET['event'] : null);
