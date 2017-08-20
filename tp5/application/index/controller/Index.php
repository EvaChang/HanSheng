<?php
namespace app\index\controller;
use think\Db;

class Index
{
    function __construct() {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Authorization,Content-Type");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];
        if($method == "OPTIONS") {
            die();
        }
    }
    public function index(){
       // return json_encode($_SERVER['HTTP_AUTHORIZATION']);
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) return json_encode(['error' => 0]);
        $auth=substr($_SERVER['HTTP_AUTHORIZATION'],0,-1);
        $user=Db::table('hs_0.user')
            ->where(['token'=>$auth])
            ->find();
        //return json_encode($user);
        if(sizeof($user)==0) return json_encode(['error' => 0]);
    }
    public function login()
    {
        if (! isset($_SERVER['HTTP_AUTHORIZATION'])) {
            echo json_encode(['error_description' => 'An error occured.']);
            exit();
        }
// private key:
        $private_key = '-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBALINH5X8NTQLJhZq
SE9KMvBZWYKYyqje66CZVc+K2XLsGqW0YNbRzU9txDhj47+W6QBreoG/EQarCFlJ
CiRQJD+YIe0V+bCUaaEelItkoq09U4IrJkYJbol1CzaKMpWCaSzePFJDRx55OspT
p/gpYvz0NioJOoTEkhKNICrkwBqtAgMBAAECgYAzSxSOYNny5ENUscmjDf0ewJ7I
wLuhapb27TWLVLTQJrSGiDBdspMzDqw4ko5J42+8bzobpq+A/ESrdB831t6Z+GQ8
vKLPSnguYS6aQhsYP2eXQu2WxD2Tjekf0ekjoc9/jz3ZFK4DuqcUisBQyBmozUR5
r9MjMqf02RfgDmsYAQJBANz74T7GejD5u81rUnlxRYUIjExSrDFyn0ojC6QfbNrG
VmSJDkoaKZrsQQhe2Ujc7Ze8mG4vTXjmZ5oFrDVaASUCQQDOQ7LhL5RdQt4H34V9
/rcyp4HgfxOHAMyBu0o07D/v1nP/YEcxfHZ8iHj/gSdw3seskOetF/mafSOPImXe
G9DpAkBaY/Erk1Xx6IToLokKwcl09B0nLv3eMAt18MXXOT92cYBvGRyuNOtlwlOL
j/iC9FN/KJaVI2YmGOCxwLZDEHC9AkEAmFEN65TDLwuOAqphXeWXS2S/WBT/Spag
brzr06ESpf3rsw5aBIUwyk3NbIDnq0YYlap8KyqlPBxlAfIY36gS4QJBAJa2at4M
VeqR1cuE/cVZV3NZgomSvm0WXu7bCAbjHX3bYkazdoBNZlCzNU2vzR4XNaZJYzX8
KqPdKDkCWPQjbpo=
-----END PRIVATE KEY-----';

        if (! $privateKey = openssl_pkey_get_private($private_key)) {
            echo json_encode(['error_description' => 'Private key is wrong.']);
            exit();
        }
// decrypt
        $decrypted = '';
        if (! openssl_private_decrypt(base64_decode($_SERVER['HTTP_AUTHORIZATION']), $decrypted, $privateKey)) {
            echo json_encode(['error_description' => 'Decrypted fail.']);
            exit();
        }
// free the private key
        openssl_free_key($privateKey);
// return interface json data
        //echo json_encode($password);
        $info=explode(':',$decrypted);
        $user=Db::table('hs_0.user')
              ->field('name,tel,id')
              ->where(['name'=>$info[0],'pwd'=>md5($info[1])])
              ->select();
        if(sizeof($user)==0) return json_encode(['error' => 0]);
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
        $token='';
        for ( $i = 0; $i < 33; $i++ )
           {
               $token.= $chars[ mt_rand(0, strlen($chars) - 1) ];
           }
        $rs=Db::table('hs_0.user')
            ->update(['token' => $token,
                     'id'=>$user[0]['id'],
                     'ltime'=>date("Y-m-d H:i:s"),
                     'etime'=>date("Y-m-d H:i:s",strtotime("+1 hour"))]);
        if($rs==0) return json_encode(['error' => 0]);
        $user['0']['token']=$token;
        return json_encode($user[0]);
    }
}
