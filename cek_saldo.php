<?php
/**
* @author Ammar F. <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
* @license RedAngel PHP Concept 2017
*/
$_p['value(user_id)'] = ""; // username
$_p['value(pswd)'] = ""; // password
$_p['value(Submit)'] = "LOGIN";
$a=curl("https://m.klikbca.com/login.jsp");
$a = explode('type="hidden"', $a);
for ($i=1;$i<count($a);$i++) {
    $b = explode('name="', $a[$i]);
    $b = explode('"', $b[1]);
    $c = explode('value="', $a[$i]);
    $c = explode('"', $c[1]);
    $_p[$b[0]] = $c[0];
}
if (isset($_p['value(user_ip)'])) {
    $op = array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$_p,CURLOPT_REFERER=>"https://m.klikbca.com/login.jsp",);
    $a=curl("https://m.klikbca.com/authentication.do", $op);
    $op = array(CURLOPT_REFERER=>"https://m.klikbca.com/authentication.do",CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>"Content-Type: application/x-www-form-urlencoded\nContent-Length: 0\n\n");
    $b=curl("https://m.klikbca.com/accountstmt.do?value(actions)=menu", $op);
    $c=explode("<td align='right'><font size='1' color='#0000a7'><b>", curl("https://m.klikbca.com/balanceinquiry.do", $op));
    $c=explode("</td>", $c[1]);
    $saldo = $c[0];
    echo "Saldo anda : ".$saldo;
} else {
    echo "Gagal menjalankan curl !";
}
function curl($url, $opz=null)
{
    $ch = curl_init($url);
    $op = array(
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_USERAGENT=>"Opera/9.80 (Android; Opera Mini/19.0.2254/37.9389; U; en) Presto/2.12.423 Version/12.16",
            CURLOPT_COOKIEJAR=>getcwd()."/cookie.txt",
            CURLOPT_COOKIEFILE=>getcwd()."/cookie.txt",
            CURLOPT_SSL_VERIFYPEER=>false,
            CURLOPT_SSL_VERIFYHOST=>false,
            CURLOPT_FOLLOWLOCATION=>true,
            CURLOPT_TIMEOUT=>30,
            CURLOPT_CONNECTTIMEOUT=>30,
        );
    if ($opz!==null) {
        foreach ($opz as $key => $value) {
            $op[$key] = $value;
        }
    }
    curl_setopt_array($ch, $op);
    $a = curl_exec($ch);
    curl_close($ch);
    return $a;
}