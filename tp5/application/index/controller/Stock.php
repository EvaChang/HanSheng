<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/22 0022
 * Time: 上午 9:50
 */

namespace app\index\controller;
use think\Db;

class Stock
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
    public function stockList(){
        $stockTable=$this->mdb.'.stock';
        $goodsTable=$this->mdb.'.goods b';
        if(isset($_GET['stock_id'])){
            $rs=Db::table($stockTable)->field('number,sum')->find($_GET['stock_id']);
            return json_encode($rs);
        }
        $rs=Db::table($stockTable)->alias('a')
            ->join($goodsTable,'a.goods_id=b.goods_id')
            ->field('a.*,b.goods_name,warn_stock,(number-warn_stock) as differ,unit')
            ->where('goods_sn|goods_name','like','%'.$_GET['search'].'%')
            ->order('differ')
            ->page($_GET['page'].',10')
            ->select();
        $data['stock']=$rs;
        $count=Db::table($stockTable)->alias('a')
            ->join($goodsTable,'a.goods_id=b.goods_id')
            ->where('goods_sn|goods_name','like','%'.$_GET['search'].'%')->count();
        $data['total_count']=$count;
        return json_encode($data);
    }
    public function stockUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $stockTable=$this->mdb.'.stock';
        $stock=Db::table($stockTable)->find($postData['stock_id']);
        $goodsTable=$this->mdb.'.goods';
        $goods=Db::table($goodsTable)->field('goods_name')->find($stock['goods_id']);
        $data['stock_id']=$postData['stock_id'];
        $data['number']=$postData['number'];
        if($stock['number']>0) {
            $data['sum'] = $stock['sum'] / $stock['number']*$postData['number'];
            }else{
            $purchaseDetail=$this->mdb.'.purchase_detail';
            $price=Db::table($purchaseDetail)
                ->field('price')
                ->where(['goods_id'=>$stock['goods_id']])
                ->order('purchase_detail_id desc')
                ->find();
            if(!$price){
                $saleDetail=$this->mdb.'.sale_detail';
                $price=Db::table($saleDetail)
                    ->field('price')
                    ->where(['goods_id'=>$stock['goods_id']])
                    ->order('sale_detail_id desc')
                    ->find();
            }
            $data['sum']=$postData['number']*$price['price'];
        }
        $adata['time']=date("Y-m-d H:i:s");
        $adata['user']=$this->user;
        $adata['goods_id']=$stock['goods_id'];
        if($stock['number']>$postData['number']){      //报损
            $adata['subject']=1;
            $adata['cost']=$stock['sum']-$data['sum'];
            $remark="商品报损:".$goods['goods_name']."，数量：".$stock['number']."=>".$postData['number'];
        }else{                                          //报溢
            $adata['subject']=2;
            $adata['income']=$data['sum']-$stock['sum'];
            $remark="商品报溢:".$goods['goods_name']."，数量：".$stock['number']."=>".$postData['number'];
        }
        $rs=Db::table($stockTable)->update($data);
        $result['result']=0;
        if(!$rs) return json_encode($result);
        $account=$this->mdb.'.account';
        $rs=Db::table($account)->insert($adata);
        if(!$rs) return json_encode($result);
        mylog($this->user,$this->mdb,$remark);
        $result['newStock']=$data;
        $result['result']=1;
        return  json_encode($result);
    }

}