<?php
/**
 * 容器Provider定义文件
 * 别名 => 类实例对象
 */

use App\Controller\TestOne;
use App\Library\Cache\File;

return [
    'testOne' => TestOne::class,
    'cache'   => File::class
];