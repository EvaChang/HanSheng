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
        $where["goods_sn|goods_name"]=['like','%'.$_GET['search'].'%'];
        if($_GET['inorder']==1) $where['inorder']=['like','IN'.'%'];
        if($_GET['inorder']==0) $where['inorder']=0;
        if($_GET['store_id']!='0') $where['store_id']=$_GET['store_id'];
        $order=$_GET['orderby']==''?'stock_id':$_GET['orderby'].',stock_id';
        $unitTable=$this->mdb.'.unit c';
        $rs=Db::table($stockTable)->alias('a')
            ->join($goodsTable,'a.goods_id=b.goods_id')
            ->join($unitTable,'b.unit_id=c.unit_id')
           // ->fetchSql(true)
            ->field('inorder,sum(a.sum) as sum,sum(a.number) as number,goods_name,a.goods_id,unit_name,b.unit_id')
            ->where($where)
            ->group('a.goods_id,inorder')
            ->order($order)
            ->page($_GET['page'].',10')
            ->select();
        //echo $rs;
        $data['stock']=$rs;
        $count=Db::table($stockTable)->alias('a')
            ->join($goodsTable,'a.goods_id=b.goods_id')
            ->where($where)
            ->group('a.goods_id,inorder')
            ->count();
        $data['total_count']=$count;
        return json_encode($data);
    }
    public function delStock(){
        $delTime=date('Y-m-d H:i:s');
        $stockDetailTable=$this->mdb.'.stock_detail';
        $data['user']=$this->user;
        $data['time']=$delTime;
        $data['inorder']=$_GET['inorder'];
        $data['remark']='删除库存：'.$_GET['info'];
        $data['goods_id']=$_GET['goods_id'];
        if($_GET['store_id']!=0)$where['store_id']=$_GET['store_id'];
        $where['goods_id']=$_GET['goods_id'];
        $where['inorder']=$_GET['inorder'];
        $stockTable=$this->mdb.'.stock';
        $delStock=Db::table($stockTable)->where($where)->select();
        $i=0;
        foreach ($delStock as $value){
            $data['store_id']=$value['store_id'];
            //$data['remark'].='数量为'.;
            $data['Dsum']=(-1)*$value['sum'];
            $data['Dstock']=(-1)*$value['number'];
            $data['sum']=$data['stock']=0;
            $rs=Db::table($stockDetailTable)->insert($data);
            if(!$rs) json_encode(['result'=>2]);
            Db::table($stockTable)->delete($value['stock_id']);

            if($value['number']!=0) {
                $adata[$i]['time'] = $delTime;
                $adata[$i]['user'] = $this->user;
                $adata[$i]['goods_id'] = $_GET['goods_id'];
                $adata[$i]['store_id'] = $value['store_id'];
                if ($value['number'] > 0) {
                    $adata[$i]['subject'] = 1;
                    $adata[$i]['cost'] = $value['sum'];
                    $adata[$i]['summary'] = $remark = "删除商品发生报损:" . $_GET['info'] . "，数量：" . $value['number'];
                } else {
                    $adata[$i]['subject'] = 2;
                    $adata[$i]['income'] = $value['sum'];
                    $adata[$i]['summary'] = $remark = "删除商品发生报溢:" . $_GET['info'] . "，数量：" . $value['number'];
                }
                $i++;
            }
            if($i>0) {
                $account = $this->mdb . '.account';
                $rs = Db::table($account)->insertAll($adata);
                if (!$rs) json_encode(['result' => 2]);
            }

        }
         return json_encode(['result'=>1]);

    }

    public function updateStock(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $updateTime=date('Y-m-d H:i:s');
        $data['time']=$updateTime;
        $data['user']=$this->user;
        $data['remark']=($postData['Dstock']>0?"报溢：":'报损：').$postData['stock']['goods_name'];
        $data['goods_id']=$postData['stock']['goods_id'];
        $data['store_id']=$postData['store_id'];
        $data['inorder']=$postData['stock']['inorder'];
        $data['Dstock']=$postData['Dstock'];
        $data['stock']=$postData['stock']['number']+$postData['Dstock'];
        if($postData['stock']['number']>0){
            $data['Dsum'] = $postData['stock']['sum'] / $postData['stock']['number']*$postData['Dstock'];
        }else{
            $purchaseDetail=$this->mdb.'.purchase_detail';
            $price=Db::table($purchaseDetail)
                ->field('price')
                ->where(['goods_id'=>$postData['stock']['goods_id']])
                ->order('purchase_detail_id desc')
                ->find();
            if(!$price){
                $saleDetail=$this->mdb.'.sale_detail';
                $price=Db::table($saleDetail)
                    ->field('price')
                    ->where(['goods_id'=>$postData['stock']['goods_id']])
                    ->order('sale_detail_id desc')
                    ->find();
            }
            $data['Dsum']=$postData['Dstock']*$price['price'];
        }
        $data['sum']=$postData['stock']['sum']+$data['Dsum'];
        $stockDetailTable=$this->mdb.'.stock_detail';
        $rs=Db::table($stockDetailTable)->insert($data);
        if(!$rs) return json_encode(['result'=>2]);
        $adata['time']=$updateTime;
        $adata['user']=$this->user;
        $adata['goods_id']=$postData['stock']['goods_id'];
        $adata['store_id']=$postData['store_id'];
        if($data['Dstock']<0){      //报损
            $adata['subject']=1;
            $adata['cost']=$data['Dsum'];
            $adata['summary']=$remark="商品报损:".$postData['stock']['goods_name']."，数量：".$postData['Dstock'];
        }else{                                          //报溢
            $adata['subject']=2;
            $adata['income']=$data['Dsum'];
            $adata['summary']=$remark="商品报溢:".$postData['stock']['goods_name']."，数量：".$postData['Dstock'];
        }
        $account=$this->mdb.'.account';
        $rs=Db::table($account)->insert($adata);
        if(!$rs) return json_encode(['result'=>3]);
        mylog($this->user,$this->mdb,$remark);
        return  json_encode(['result'=>1]);
    }
    public function stockDetail(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $where=$postData['where'];
        if($postData['search']!='') $where['remark']=['like','%'.$postData['search'].'%'];
        $stockDetailTable=$this->mdb.'.stock_detail';
        $rs=Db::table($stockDetailTable)
            ->where($where)
            ->order('time desc')
            ->page($postData['page'],10)
            ->select();
        $data['stockDetail']=$rs;
        $count=Db::table($stockDetailTable)
            ->where($where)
           ->count();
        $data['total_count']=$count;
        return json_encode($data);
    }
    public function exchange()
    {
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $stockTable = $this->mdb . '.stock a';
        $storeTable = $this->mdb . '.store b';
        $data['goods_id'] = $postData['goods_id'];
        $data['inorder'] = $postData['inorder'];
        $data['a.store_id'] = $postData['trans']['inStoreId'];
        $in = Db::table($stockTable)
            ->join($storeTable, 'a.store_id=b.store_id')
            ->where($data)
            ->find();
        $data['a.store_id'] = $postData['trans']['outStoreId'];
        $out = Db::table($stockTable)
            ->join($storeTable, 'a.store_id=b.store_id')
            ->where($data)
            ->find();
        unset($data['a.store_id']);
        $data['store_id']=$postData['trans']['outStoreId'];
        $data['time'] = date('Y-m-d H:i:s');
        $data['user'] = $this->user;
        $data['Dstock'] = $postData['trans']['Dstock'] * (-1);
        //print_r($out);
        if ($out['number'] > 0) {
            $data['Dsum'] = $out['sum'] / $out['number'] * $data['Dstock'];
        } else {
            $purchaseDetail = $this->mdb . '.purchase_detail';
            $price = Db::table($purchaseDetail)
                ->field('price')
                ->where(['goods_id' => $postData['goods_id']])
                ->order('purchase_detail_id desc')
                ->find();
            if (!$price) {
                $saleDetail = $this->mdb . '.sale_detail';
                $price = Db::table($saleDetail)
                    ->field('price')
                    ->where(['goods_id' => $postData['goods_id']])
                    ->order('sale_detail_id desc')
                    ->find();
            }
            $data['Dsum'] = $data['Dstock'] * $price['price'];
        }
        $data['sum'] = $out['sum'] + $data['Dsum'];
        $data['stock'] = $out['number'] + $data['Dstock'];
        $data['remark'] = '调出到：' . $in['store_name'] . (isset($postData['trans']['remark'])?$postData['trans']['remark']:'');
        $stockDetailTable = $this->mdb . '.stock_detail';
        $rs = Db::table($stockDetailTable)->insert($data);
        if (!$rs) json_encode(['result' => 0]);
        $data['store_id'] = $postData['trans']['inStoreId'];
        $data['Dstock'] = $postData['trans']['Dstock'];
        $data['Dsum'] = (-1) * $data['Dsum'];
        $data['stock'] = $in['number'] + $data['Dstock'];
        $data['sum'] = $in['sum'] + $data['Dsum'];
        $data['remark'] = '调入由：' . $out['store_name'] . (isset($postData['trans']['remark'])?$postData['trans']['remark']:'');
        $stockDetailTable = $this->mdb . '.stock_detail';
        $rs = Db::table($stockDetailTable)->insert($data);
        if (!$rs) json_encode(['result' => 0]);
        return json_encode(['result' => 1]);
    }
    public function getUnit(){
        $unitTable=$this->mdb.'.unit b';
        $unit_priceTable=$this->mdb.'.unit_price a';
        $where['goods_id']=$_GET['goods_id'];
        $rs=Db::table($unit_priceTable)
            ->join($unitTable,'a.unit_id=b.unit_id')
            ->where($where)
            ->field('a.unit_id,unit_name')
            ->select();
        $unit=Db::table($unitTable)->find($_GET['unit_id']);
        $rs[sizeof($rs)]=$unit;
        return json_encode($rs);
    }
    public function transfer(){
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $time=date('Y-m-d H:i:s');
        //插入sale表
        $remark=(isset($postData['remark'])?$postData['remark']:'')
            ."商品拆装成".$postData['in']['goods_name']."-数量:".$postData['in']['number'].',价格:'
            .$postData['in']['price'];
        $saleTable=$this->mdb.'.sale';
        $sale['member_id']=0;
        $sale['time']=$time;
        $sale['sum']=$postData['out']['number']*$postData['out']['price'];
        $sale['user']=$this->user;
        $sale['type']="transfer";
        $sale['summary']=$remark;
        $sale_id=Db::table($saleTable)->insertGetId($sale);
        if(!$sale_id) return json_encode(['result'=>0]);
        Db::table($saleTable)->delete($sale_id);
        //插入sale_detail表
        $saleDetailTable=$this->mdb.'.sale_detail';
        $sDetail['sale_id']=$sale_id;
        $where['goods_id']=$sDetail['goods_id']=$postData['out']['goods_id'];
        $sDetail['number']=$postData['out']['number'];
        $sDetail['price']=$postData['out']['price'];
        $sDetail['sum']=$sale['sum'];
        $where['store_id']=$sDetail['store_id']=$postData['store_id'];
        $where['inorder']=$sDetail['inorder']=$postData['out']['inorder'];
        $sDetail['unit_id']=$postData['out']['unit_id'];
        $sDetail_id=Db::table($saleDetailTable)->insertGetId($sDetail);
        if(!$sDetail_id) return json_encode(['result'=>0]);
        //查询stock
        $stockTable=$this->mdb.'.stock';
        $stock=Db::table($stockTable)->where($where)->find();
        //插入account表
        $aData['sale_detail_id']=$sDetail_id;
        $aData['goods_id']=$postData['out']['goods_id'];
        $aData['subject']=0;
        $aData['cost']=$stock['sum']/$stock['number']*$postData['out']['number'];
        $aData['income']=$sale['sum'];
        $aData['time']=$time;
        $aData['user']=$this->user;
        $aData['store_id']=$postData['store_id'];
        $aData['summary']=$remark;
        $accountTable=$this->mdb.'.account';
        $rs=Db::table($accountTable)->insert($aData);
        if(!$rs) return json_encode(['result'=>0]);
        //插入stock_detail表
        $stockDetail['goods_id']=$postData['out']['goods_id'];
        $stockDetail['inorder']=$postData['out']['inorder'];
        $stockDetail['store_id']=$postData['store_id'];
        $stockDetail['Dstock']=(-1)*$postData['out']['number'];
        $stockDetail['stock']=$stock['number']+$stockDetail['Dstock'];
        $stockDetail['Dsum']=(-1)*$aData['cost'];
        $stockDetail['sum']=$stock['sum']+$stockDetail['Dsum'];
        $stockDetail['time']=$time;
        $stockDetail['remark']=$remark;
        $stockDetail['type']='sale';
        $stockDetail['user']=$this->user;
        $stockDetailTable=$this->mdb.'.stock_detail';
        $rs=Db::table($stockDetailTable)->insert($stockDetail);
        if(!$rs) return json_encode(['result'=>0]);

        //查询是否加入inorder
        $remark=(isset($postData['remark'])?$postData['remark']:'')
            ."由商品拆装生成:".$postData['out']['goods_name']."-数量:".$postData['out']['number'].',价格:'
            .$postData['out']['price'];
        $pWhere['goods_id']=$postData['in']['goods_id'];
        $pWhere['store_id']=$postData['store_id'];
        $pWhere['inorder']=['like','IN%'];
        $check=Db::table($stockTable)->where($pWhere)->select();
        if(!$check) {
            $inorder=0;
        }else{
            $purchaseTable = $this->mdb . '.purchase_all';
            $inorder='IN'.date("Ymd").'-';
            $iwhere['inorder']=['like', $inorder . '%'];
            $rs=Db::table($purchaseTable)
                ->where($iwhere)
                ->count();
            $i=$rs+1;
            while($rs>0){
                $iwhere['inorder']=['like', $inorder .$i. '%'];
                $rs=Db::table($purchaseTable)
                    ->where($iwhere)
                    ->count();
            }
            $inorder.=$i;
        }
         //purchase表
        $purchaseTable=$this->mdb.'.purchase';
        $pData['sum']=$postData['in']['price']*$postData['in']['number'];
        $pData['supply_id']=0;
        $pData['inorder']=$inorder;
        $pData['time']=$time;
        $pData['user']=$this->user;
        $pData['summary']=$remark;
        $pData['type']='transfer';
        $purchase_id=Db::table($purchaseTable)->insertGetId($pData);
        Db::table($purchaseTable)->delete($purchase_id);
        if(!$purchase_id) return json_encode(['result'=>0]);
        //purchase_detail
        $pdetail['goods_id']=$postData['in']['goods_id'];
        $pdetail['number']=$postData['in']['number'];
        $pdetail['price']=$postData['in']['price'];
        $pdetail['sum']=$postData['in']['number']*$postData['in']['price'];
        $pdetail['store_id']=$postData['store_id'];
        $pdetail['unit_id']=$postData['in']['unit_id'];
        $pdetail['purchase_id']=$purchase_id;
        $purchaseDetailTable=$this->mdb.'.purchase_detail';
        $rs=Db::table($purchaseDetailTable)->insert($pdetail);
        if(!$rs) return json_encode(['result'=>0]);
        //stock_detail
        unset($where);
        $where['goods_id']=$stockDetail['goods_id']=$postData['in']['goods_id'];
        $where['inorder']=$stockDetail['inorder']=$inorder;
        $where['store_id']=$stockDetail['store_id']=$postData['store_id'];
        $stock=Db::table($stockTable)->where($where)->find();
        if($postData['in']['basic_unit']!=$postData['in']['unit_id']){
            $unit_priceTable=$this->mdb.'.unit_price';
            $uWhere['goods_id']=$postData['in']['goods_id'];
            $uWhere['unit_id']=$postData['in']['unit_id'];
            $fx=Db::table($unit_priceTable)->where($uWhere)->find();
            $stockDetail['Dstock']=$postData['in']['number']/$fx['fx'];
        }else{
            $stockDetail['Dstock']=$postData['in']['number'];
        }
        $stockDetail['stock']=$stock['number']+$stockDetail['Dstock'];
        $stockDetail['Dsum']=$pdetail['sum'];
        $stockDetail['sum']=$stock['sum']+$stockDetail['Dsum'];
        $stockDetail['time']=$time;
        $stockDetail['remark']=$remark;
        $stockDetail['type']='purchase';
        $stockDetail['user']=$this->user;
        $stockDetailTable=$this->mdb.'.stock_detail';
        $rs=Db::table($stockDetailTable)->insert($stockDetail);
        if(!$rs) return json_encode(['result'=>0]);
        return json_encode(['result'=>1]);
    }
    public function carryCheck(){
        $stockTable=$this->mdb.'.stock';
        $where['goods_id']=$_GET['goods_id'];
        $where['store_id']=$_GET['store_id'];
        $rs=Db::table($stockTable)->where($where)->field('inorder')->order('stock_id desc,inorder desc')->find();
        if($rs['inorder']!=$_GET['inorder']) return json_encode(1);
        return json_encode(0);
    }
    public function carry(){
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $time=date('Y-m-d H:i:s');
        $remark="商品结转下批次"."-数量:".$postData['number'];
        //查询stock
        $where['store_id']=$postData['store_id'];
        $where['inorder']=$postData['stock']['inorder'];
        $where['goods_id']=$postData['stock']['goods_id'];
        $stockTable=$this->mdb.'.stock';
        $stock=Db::table($stockTable)->where($where)->find();
        //插入sale表
        $saleTable=$this->mdb.'.sale';
        $sale['member_id']=0;
        $sale['time']=$time;
        $sale['sum']=$postData['number']*$stock['sum']/$stock['number'];
        $sale['user']=$this->user;
        $sale['type']="carry";
        $sale['summary']=$remark;
        $sale_id=Db::table($saleTable)->insertGetId($sale);
        if(!$sale_id) return json_encode(['result'=>0]);
        Db::table($saleTable)->delete($sale_id);
        //插入sale_detail表
        $saleDetailTable=$this->mdb.'.sale_detail';
        $sDetail['sale_id']=$sale_id;
        $sDetail['goods_id']=$postData['stock']['goods_id'];
        $sDetail['number']=$postData['number'];
        $sDetail['price']=$stock['sum']/$stock['number'];
        $sDetail['sum']=$sale['sum'];
        $sDetail['store_id']=$postData['store_id'];
        $sDetail['inorder']=$postData['stock']['inorder'];
        $sDetail['unit_id']=$postData['stock']['unit_id'];
        $sDetail_id=Db::table($saleDetailTable)->insertGetId($sDetail);
        if(!$sDetail_id) return json_encode(['result'=>0]);

        //插入account表
        $aData['sale_detail_id']=$sDetail_id;
        $aData['goods_id']=$postData['stock']['goods_id'];
        $aData['subject']=0;
        $aData['cost']=$sale['sum'];
        $aData['income']=$sale['sum'];
        $aData['time']=$time;
        $aData['user']=$this->user;
        $aData['store_id']=$postData['store_id'];
        $aData['summary']=$remark;
        $accountTable=$this->mdb.'.account';
        $rs=Db::table($accountTable)->insert($aData);
        if(!$rs) return json_encode(['result'=>0]);
        //插入stock_detail表
        $stockDetail['goods_id']=$postData['stock']['goods_id'];
        $stockDetail['inorder']=$postData['stock']['inorder'];
        $stockDetail['store_id']=$postData['store_id'];
        $stockDetail['Dstock']=(-1)*$stock['number'];
        $stockDetail['stock']=0;
        $stockDetail['Dsum']=(-1)*$sale['sum'];
        $stockDetail['sum']=0;
        $stockDetail['time']=$time;
        $stockDetail['remark']=$remark;
        $stockDetail['type']='sale';
        $stockDetail['user']=$this->user;
        $stockDetailTable=$this->mdb.'.stock_detail';
        $rs=Db::table($stockDetailTable)->insert($stockDetail);
        if(!$rs) return json_encode(['result'=>0]);

        //查询是否加入inorder
        $remark="由商品结转生成:数量:".$postData['number'];
        $pWhere['goods_id']=$postData['stock']['goods_id'];
        $pWhere['store_id']=$postData['store_id'];
        $rs=Db::table($stockTable)->where($pWhere)->field('inorder')->order('stock_id desc,inorder desc')->find();
        $inorder=$rs['inorder'];
        //purchase表
        $purchaseTable=$this->mdb.'.purchase';
        $pData['sum']=$sale['sum'];
        $pData['supply_id']=0;
        $pData['inorder']=$inorder;
        $pData['time']=$time;
        $pData['user']=$this->user;
        $pData['summary']=$remark;
        $pData['type']='carry';
        $purchase_id=Db::table($purchaseTable)->insertGetId($pData);
        Db::table($purchaseTable)->delete($purchase_id);
        if(!$purchase_id) return json_encode(['result'=>0]);
        //purchase_detail
        $pdetail['goods_id']=$postData['stock']['goods_id'];
        $pdetail['number']=$postData['number'];
        $pdetail['price']=$sDetail['price'];
        $pdetail['sum']=$sale['sum'];
        $pdetail['store_id']=$postData['store_id'];
        $pdetail['unit_id']=$postData['stock']['unit_id'];
        $pdetail['purchase_id']=$purchase_id;
        $purchaseDetailTable=$this->mdb.'.purchase_detail';
        $rs=Db::table($purchaseDetailTable)->insert($pdetail);
        if(!$rs) return json_encode(['result'=>0]);
        //stock_detail
        unset($where);
        $where['goods_id']=$stockDetail['goods_id']=$postData['stock']['goods_id'];
        $where['inorder']=$stockDetail['inorder']=$inorder;
        $where['store_id']=$stockDetail['store_id']=$postData['store_id'];
        $stock=Db::table($stockTable)->where($where)->find();
        $stockDetail['Dstock']=$postData['number'];
        $stockDetail['stock']=$stock['number']+$stockDetail['Dstock'];
        $stockDetail['Dsum']=$sale['sum'];
        $stockDetail['sum']=$stock['sum']+$stockDetail['Dsum'];
        $stockDetail['time']=$time;
        $stockDetail['remark']=$remark;
        $stockDetail['type']='purchase';
        $stockDetail['user']=$this->user;
        $stockDetailTable=$this->mdb.'.stock_detail';
        $rs=Db::table($stockDetailTable)->insert($stockDetail);
        if(!$rs) return json_encode(['result'=>0]);
        return json_encode(['result'=>1]);

    }

}