 <?php
/**
*@param $ip target ip
*@param $times ping times
*注意：服务器是否安装ping
*exec("ping 120.31.70.142 -c 4",$info,$error);
*$error不为0时，是有问题
*/
function ping($ip,$times=4)
{  
  $info = array();
  if(!is_numeric($times) ||  $times-4<0)
  {
    $times = 4;
  }
  /**
   * PATH_SEPARATOR  ['windows' => ';', 'linux' => ':']
   * DIRECTORY_SEPARATOR 自适应分隔符
   */
  if (PATH_SEPARATOR==':' || DIRECTORY_SEPARATOR=='/')//linux
  {
    exec("ping $ip -c $times",$info);
    if (count($info) < 9)
    {
      $info['error']='timeout';
    }
  }
  else //windows
  {
    exec("ping $ip -n $times",$info);
    if (count($info) < 10)
    {
      $info['error']='timeout';
    }
  }
  return $info;
}
// $ip = '116.31.103.102';//IP地址
// $ip = '192.168.71.28';//IP地址
$ip = '120.31.70.142';
print_r(ping($ip));
sss
?>

