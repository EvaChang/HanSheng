<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/14 0014
 * Time: 上午 7:56
 */

namespace app\index\controller;
use think\Db;

class Statistics
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
    public function dashGetSum(){
        $saleTable=$this->mdb.'.sale_all';
        $where['time']=['like',date("Y-m-d").'%'];
        $where['type']='S';
        $data['sum']=Db::table($saleTable)
            ->where($where)
            ->sum('sum');
        $where['type']='B';
        $data['back']=Db::table($saleTable)
            ->where($where)
            ->sum('sum');
        $purchaseTable=$this->mdb.'.purchase_all';
        $supplyTable=$this->mdb.'.supply b';
        $data['onWay']=Db::table($purchaseTable)->alias('a')
            ->join($supplyTable,'a.supply_id=b.supply_id')
           // ->fetchSql(true)
            ->where(['on_way'=>1])
            ->field('supply_name,time,purchase_id,finish')
            ->select();

        return json_encode($data);

    }
    public function saleToday(){
        $saleTable=$this->mdb.'.sale_all';
        $where['time']=['like',date("Y-m-d").'%'];
        $where['type']=$_GET['type'];
        $saleDetailTable=$this->mdb.'.sale_detail';
        $goodsTable=$this->mdb.'.goods';
        $rs=Db::table($saleTable)->alias('a')
            ->join($saleDetailTable.' b','a.sale_id=b.sale_id')
            ->join($goodsTable.' c','c.goods_id=b.goods_id')
            ->where($where)
            ->field('goods_name, sum(b.number) as number,sum(b.sum) as sum ')
            ->order('number desc')
            ->page($_GET['page'],10)
            ->group('b.goods_id')
            ->select();
        $data['lists']=$rs;
        $total_count=Db::table($saleTable)->alias('a')
                     ->join($saleDetailTable.' b','a.sale_id=b.sale_id')
                     ->where($where)
                     ->count('distinct b.goods_id');
        $data['total_count']=$total_count;
        return json_encode($data);
    }
    public function onWay(){
        $purchaseDetailTable=$this->mdb.'.purchase_detail';
        $goodsTable=$this->mdb.'.goods b';
        $data['lists']=Db::table($purchaseDetailTable)->alias('a')
            ->join($goodsTable,'a.goods_id=b.goods_id')
            ->where(['purchase_id'=>$_GET['purchase_id']])
            ->page($_GET['page'],10)
            ->field('goods_name,number,sum')
            ->select();
        $data['total_count']=Db::table($purchaseDetailTable)
            ->where(['purchase_id'=>$_GET['purchase_id']])
            ->count();
        return json_encode($data);
    }
    public function receiveGoods(){
        $purchaseTable=$_GET['finish']==1?$this->mdb.'.purchase_finish':$this->mdb.'.purchase';
        $rs=Db::table($purchaseTable)
            ->where(['purchase_id'=>$_GET['purchase_id']])
            //->fetchSql(true)
            ->update(['on_way'=>0]);
      //  echo $rs;
        $purchaseTable=$this->mdb.'.purchase_all';
        $supplyTable=$this->mdb.'.supply b';
        $data['onWay']=Db::table($purchaseTable)->alias('a')
            ->join($supplyTable,'a.supply_id=b.supply_id')
            ->where(['on_way'=>1])
            ->field('supply_name,time,purchase_id,finish')
            ->select();
        return json_encode($data);

    }

}