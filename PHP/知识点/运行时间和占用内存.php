<?php

$t1 = microtime(true);

//  运行的代码

$t2 = microtime(true);

echo '耗时'.round($t2-$t1,3).'秒<br>';
echo 'Now memory_get_usage: ' . memory_get_usage() . '<br />';

