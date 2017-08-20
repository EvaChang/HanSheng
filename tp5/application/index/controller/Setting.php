<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/13
 * Time: 7:21
 */

namespace app\index\controller;
use think\Db;

class Setting
{
    private $mdb='hs_';
    private $user='';
    function __construct() {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Authorization,Content-Type");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];
        if($method == "OPTIONS") {
            die();
        }
        if (! isset($_SERVER['HTTP_AUTHORIZATION'])) die();
        $auth=substr($_SERVER['HTTP_AUTHORIZATION'],0,-1);
        $user=Db::table('hs_0.user')
            ->where(['token'=>$auth])
            ->find();
        if(sizeof($user)==0) die();
        $this->mdb.=(string)$user['MDB'];
        $this->user=$user['name'];
    }
    public function info(){
        $infoTable=$this->mdb.'.info';
        $rs=Db::table($infoTable)->find(1);
        return json_encode($rs);
    }
    public function editInfo(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $infoTable=$this->mdb.'.info';
        $rs=Db::table($infoTable)
            ->where('id',1)
            ->update($postData);
        return $rs;
    }
    public function cat(){
        $catTable=$this->mdb.'.cat';
        $rs=Db::table($catTable)->select();
        return json_encode($rs);
    }
    public  function catCreate(){
        $catTable=$this->mdb.'.cat';
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $data['cat_name']=$postData['cat_name'];
        $rs=Db::table($catTable)
            ->insert($data);
        if($rs==1) mylog($this->user,$this->mdb,'新建种类:'.$data['cat_name']);
        return json_encode(['result'=>$rs]);
    }
    public function catUpdate(){
        $catTable=$this->mdb.'.cat';
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $data['cat_name']=$postData['cat_name'];
        $data['cat_id']=$postData['cat_id'];
        $odata = Db::table($catTable)->find($postData['cat_id']);
        $rs=Db::table($catTable)
            ->update($data);
        if($rs==1) mylog($this->user,$this->mdb,$odata['cat_name'].'更新种类为:'.$data['cat_name']);
        return json_encode(['result'=>$rs]);
    }
    public function catDelete(){
        $goodsTable=$this->mdb.'.goods';
        $postData=$_GET;
        $rs=Db::table($goodsTable)->where($postData)->find();
        if(sizeof($rs)==0) {
            $catTable=$this->mdb.'.cat';
            $data = Db::table($catTable)->find($postData['cat_id']);
            $rs = Db::table($catTable)->delete($postData['cat_id']);
            if($rs==1) mylog($this->user,$this->mdb,'删除种类:'.$data['cat_name']);
            return json_encode(['result' => $rs]);
        }else return json_encode(['result' => 2]);
    }
    public function goods(){
        $goodsTable=$this->mdb.'.goods';
      //  $unitTable=$this->mdb.'.unit b';
        $rs = Db::table($goodsTable)
            ->select();
        return json_encode($rs);
    }
    public function goodsQuery(){
        $goodsTable=$this->mdb.'.goods';
        $rs = Db::table($goodsTable)
            ->where(['is_show'=>1])
            ->where('goods_name|goods_sn','like','%'.$_GET['search'].'%')
           // ->whereOr('goods_sn','like','%'.$_GET['search'].'%')
            ->field('goods_id,goods_name,unit,promote,warn_stock')
            ->page(1,10)
            ->select();
        return json_encode($rs);
    }
    public function goodsUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $data['goods_name']=$postData['goods_name'];
        $data['goods_sn']=$postData['goods_sn'];
        $data['warn_stock']=$postData['warn_stock'];
        $data['unit']=$postData['unit'];
        $data['out_price']=$postData['out_price'];
        $data['promote']=$postData['promote']?1:0;
        $data['is_show']=$postData['is_show']?1:0;
        $data['cat_id']=$postData['cat_id']['cat_id'];
        $goodsTable=$this->mdb.'.goods';
        if($postData['goods_id']==0){
            $rs=Db::table($goodsTable)
                ->insert($data);
            $remark[0]='添加新产品'.$data['goods_name'];
        }else{
            $remark=Db::table($goodsTable)->find($postData['goods_id']);
            $data['goods_id']=$postData['goods_id'];
            $rs=Db::table($goodsTable)
                ->update($data);
        }
        if($rs){
            mylog($this->user,$this->mdb,'修改产品成功,old data:'.implode('|',$remark));
            return json_encode(['result'=>$rs]);
        }
    }
    public function pwdUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
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
        if (! openssl_private_decrypt(base64_decode($postData['data']), $decrypted, $privateKey)) {
            echo json_encode(['error_description' => 'Decrypted fail.']);
            exit();
        }
// free the private key
        openssl_free_key($privateKey);
// return interface json data
        //echo json_encode($password);
        $info=explode(':',$decrypted);
        $user=Db::table('hs_0.user')
            ->where(['name'=>$info[0],'pwd'=>md5($info[1])])
            ->find();
        if($user){
            $data['id']=$user['id'];
            $data['pwd']=md5($info[2]);
            $rs=Db::table('hs_0.user')
                ->update($data);
            if($rs) return json_encode(['result'=>1]);
        }else{
            return json_encode(['result'=>0]);
        }
    }
    public function store(){
        $storeTable=$this->mdb.'.store';
        $rs=Db::table($storeTable)->order('store_id')->select();
        return json_encode(['store'=>$rs]);
    }
    public function storeUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $storeTable=$this->mdb.'.store';
        if($postData['store_id']==''){
            $rs=Db::table($storeTable)
                ->where(['store_name'=>$postData['store_name']])
                ->find();
            if($rs) return json_encode(['result'=>3]);
            $rs=Db::table($storeTable)
                ->insert($postData);
            if($rs) return json_encode(['result'=>1]);
        }else{
            $data['store_id']=$postData['store_id'];
            $data['store_name']=$postData['store_name'];
            $rs=Db::table($storeTable)
                ->update($data);
            if($rs) return json_encode(['result'=>1]);
        }
        return json_encode(['result'=>0]);
    }
    public function staff(){
        $staffTable=$this->mdb.'.staff';
        $rs=Db::table($staffTable)
            ->where('staff_name','like','%'.$_GET['search'].'%')
            ->whereOr('staff_sn','like','%'.$_GET['search'].'%')
            ->page($_GET['page'].',10')
            ->select();
        $count=Db::table($staffTable)
            ->where('staff_name','like','%'.$_GET['search'].'%')
            ->whereOr('staff_sn','like','%'.$_GET['search'].'%')
            ->count();
        $data['staff']=$rs;
        $data['total_count']=$count;
        return json_encode($data);
    }
    public function staffSearch(){
        $staffTable=$this->mdb.'.staff';
        $rs=Db::table($staffTable)
            ->where('staff_name','like','%'.$_GET['search'].'%')
            ->whereOr('staff_sn','like','%'.$_GET['search'].'%')
            ->field('staff_id,staff_name')
            ->page($_GET['page'].',10')
            ->select();
        $data['staff']=$rs;
        return json_encode($data);
    }
    public function staffUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $staffTable=$this->mdb.'.staff';
        if($postData['staff_id']==''){
            $rs=Db::table($staffTable)
                ->where(['staff_name'=>$postData['staff_name']])
                ->find();
            if($rs) return json_encode(['result'=>3]);
            $rs=Db::table($staffTable)
                ->insert($postData);
            if($rs) return json_encode(['result'=>1]);
        }else{
            $data['staff_id']=$postData['staff_id'];
            $data['staff_sn']=$postData['staff_sn'];
            $data['staff_name']=$postData['staff_name'];
            $data['staff_phone']=$postData['staff_phone'];
            $rs=Db::table($staffTable)
                ->update($data);
            if($rs) return json_encode(['result'=>1]);
        }
        return json_encode(['result'=>0]);
    }
    public function unit(){
        $unitTable=$this->mdb.'.unit';
        if(!isset($_GET['search'])){
            $rs=Db::table($unitTable)->select();
                return json_encode($rs);
        }
        $rs=Db::table($unitTable)
            ->where('unit_name','like','%'.$_GET['search'].'%')
            ->page($_GET['page'].',10')
            ->select();
        $count=Db::table($unitTable)
            ->where('unit_name','like','%'.$_GET['search'].'%')
            ->count();
        $data['unit']=$rs;
        $data['total_count']=$count;
        return json_encode($data);
    }
    
    public function unitUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $unitTable=$this->mdb.'.unit';
        if($postData['unit_id']==''){
            $rs=Db::table($unitTable)
                ->where(['unit_name'=>$postData['unit_name']])
                ->find();
            if($rs) return json_encode(['result'=>3]);
            $rs=Db::table($unitTable)
                ->insert($postData);
            if($rs) return json_encode(['result'=>1]);
        }else{
            $data['unit_id']=$postData['unit_id'];
            $data['unit_name']=$postData['unit_name'];
            $rs=Db::table($unitTable)
                ->update($data);
            if($rs) return json_encode(['result'=>1]);
        }
        return json_encode(['result'=>0]);
    }
    

}