<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/21
 * Time: 6:29
 */

namespace app\index\controller;
use think\Db;

class Purchase
{
    private $mdb = 'hs_';
    private $user = '';

    function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Authorization,Content-Type");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == "OPTIONS") {
            die();
        }
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) die();
        $auth = substr($_SERVER['HTTP_AUTHORIZATION'], 0, -1);
        $user = Db::table('hs_0.user')
            ->where(['token' => $auth])
            ->find();
        if (sizeof($user) == 0) die();
        $this->mdb .= (string)$user['MDB'];
        $this->user = $user['name'];
    }

    public function supply()
    {
        $supplyTable = $this->mdb . '.supply';
        $rs = Db::table($supplyTable)
            ->where('supply_name', 'like', '%' . $_GET['search'] . '%')
            ->whereOr('supply_sn', 'like', '%' . $_GET['search'] . '%')
            ->page($_GET['page'] . ',10')
            ->select();
        $count = Db::table($supplyTable)
            ->where('supply_name', 'like', '%' . $_GET['search'] . '%')
            ->whereOr('supply_sn', 'like', '%' . $_GET['search'] . '%')
            ->count();
        $data['supply'] = $rs;
        $data['total_count'] = $count;
        return json_encode($data);
    }

    public function supplySearch()
    {
        $supplyTable = $this->mdb . '.supply';
        $rs = Db::table($supplyTable)
            ->where('supply_name', 'like', '%' . $_GET['search'] . '%')
            ->whereOr('supply_sn', 'like', '%' . $_GET['search'] . '%')
            ->field('supply_id,supply_name')
            ->page($_GET['page'] . ',10')
            ->select();
        $data['supply'] = $rs;
        return json_encode($data);
    }

    public function supplyUpdate()
    {
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $supplyTable = $this->mdb . '.supply';
        if ($postData['supply_id'] == '') {
            $rs = Db::table($supplyTable)
                ->where(['supply_name' => $postData['supply_name']])
                ->find();
            if ($rs) return json_encode(['result' => 3]);
            $rs = Db::table($supplyTable)
                ->insert($postData);
            if ($rs) return json_encode(['result' => 1]);
        } else {
            $data['supply_id'] = $postData['supply_id'];
            $data['supply_sn'] = $postData['supply_sn'];
            $data['supply_name'] = $postData['supply_name'];
            $data['supply_phone'] = $postData['supply_phone'];
            $rs = Db::table($supplyTable)
                ->update($data);
            if ($rs) return json_encode(['result' => 1]);
        }
        return json_encode(['result' => 0]);
    }

    public function purchase()
    {
        $purchase['summary'] = '';
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $back = $postData['back'];
        $insertTime = date("Y-m-d H:i:s");
        $purchaseTable = $this->mdb . '.purchase';
        $inorder=$postData['inorder']==''?0:$postData['inorder'];
        //插入purchase表
        if (sizeof($postData['detail']) > 0) {
            $purchase['inorder']=$inorder;
            $purchase['supply_id'] = $postData['data']['supply']['supply_id'];
            $purchase['sum'] = $postData['data']['ttl_sum'] * $back;
            if (isset($postData['data']['summary'])) $purchase['summary'] .= $postData['data']['summary'];
            if (isset($postData['data']['on_way'])) $purchase['on_way'] = 1;
            $purchase['time'] = $insertTime;
            if ($back == -1) $purchase['summary'] = '退货：' . $purchase['summary'];
            $purchase['type'] = $back == 1 ? 'S' : 'B';     //S--一般进货销售记录，P--付款记录，CF--结转
            $purchase['user'] = $this->user;
            $purchase_id = Db::table($purchaseTable)
                ->insertGetId($purchase);
            if (!$purchase_id) return json_encode(['result' => 0]);
            //插入purchaseDetail,stock_detail 表
            $detail = [];
            $stockDetail=[];
            $i = 0;
            $stockTable=$this->mdb.'.stock';
            $unit_priceTable=$this->mdb.'.unit_price';
            foreach ($postData['detail'] as $value) {
                $detail[$i]['purchase_id'] = $purchase_id;
                $detail[$i]['goods_id'] = $value['goods_id'];
                $detail[$i]['number'] = $value['number'] * $back;
                $detail[$i]['price'] = $value['price'];
                $detail[$i]['sum'] = $value['sum'] * $back;
                $detail[$i]['store_id'] = $value['store_id'];
                $detail[$i]['unit_id'] = $value['unit_id'];
                //stock_detail数据
                $where['goods_id']=$value['goods_id'];
                $where['store_id']=$value['store_id'];
                $where['inorder']=$inorder;
                $stock=Db::table($stockTable)->where($where)->find();
                if(!$stock){$stock['number']=0;$stock['sum']=0;}
                //echo print_r($stock);
                $stockDetail[$i]['goods_id']=$value['goods_id'];
                $stockDetail[$i]['store_id']=$value['store_id'];
                $stockDetail[$i]['inorder']=$inorder;
                $stockDetail[$i]['type']='purchase';
                $stockDetail[$i]['time']=$insertTime;
                $stockDetail[$i]['user']=$this->user;
                $tmpNumber=$value['number'];
                if($value['unit_check']){
                    $uWhere['goods_id']=$value['goods_id'];
                    $uWhere['unit_id']=$value['unit_id'];
                    $uRs=Db::table($unit_priceTable)->where($uWhere)->find();
                    $tmpNumber=$value['number']/$uRs['fx'];
                }
                $stockDetail[$i]['Dstock']=$tmpNumber;
                $stockDetail[$i]['Dsum']=$value['sum'];
                $stockDetail[$i]['stock']=$stock['number']+$tmpNumber;
                $stockDetail[$i]['sum']=$stock['sum']+$value['sum'];
               // echo $stockDetail[$i]['stock'];
                $i++;
            }
            $purchaseDetailTable = $this->mdb . '.purchase_detail';
            $rs = Db::table($purchaseDetailTable)
                ->insertAll($detail);
            if (!$rs) return json_encode(['result' => 2]);
            $stockDetailTable=$this->mdb . '.stock_detail';
            $rs = Db::table($stockDetailTable)
                ->insertAll($stockDetail);
            if (!$rs) return json_encode(['result' => 2]);


        }
        //插入bankDetail表中
        if (isset($postData['pay']['paid_sum']) && ($postData['pay']['paid_sum'] != 0)) {
            $pay['sum'] = $postData['pay']['paid_sum'] * $back * (-1);
            $pay['bank_id'] = $postData['pay']['bank'];
            $pay['time'] = $insertTime;
            $string = $back == 1 ? '付款给供应商：' : '退货，供应商退款：';
            $pay['summary'] = $string . $postData['data']['supply']['supply_name'];
            $pay['user'] = $this->user;
            $pay['mytable'] = 'purchase';
            $bankTable = $this->mdb . '.bank';
            $rs = Db::table($bankTable)->find($pay['bank_id']);
            $pay['balance'] = $rs['balance'] + $pay['sum'];
            $bankDetailTable = $this->mdb . '.bdetail';
            $rs = Db::table($bankDetailTable)
                ->insert($pay);
            if (!$rs) return json_encode(['result' => 2]);
            //在purchase表中，插入付款信息。
            $purchasePay['supply_id'] = $postData['data']['supply']['supply_id'];
            $purchasePay['sum'] = $pay['sum'] * $back;
            $purchasePay['time'] = $insertTime;
            $purchasePay['type'] = 'P';
            $purchasePay['user'] = $this->user;
            $purchasePayId = Db::table($purchaseTable)
                ->insertGetId($purchasePay);
            if (!$purchasePayId) return json_encode(['result' => 2]);
            //该supply_id 的sum(sum)是否为0，为0则删除。
            $sum = Db::table($purchaseTable)
                ->where(['supply_id' => $postData['data']['supply']['supply_id']])
                ->sum('sum');
            if ($sum == 0) {
                $query = 'update ' . $purchaseTable . ' set cf=' . $purchasePayId . ' where supply_id=' . $postData['data']['supply']['supply_id'] . ' and purchase_id <>' . $purchasePayId;
                Db::execute($query);
                $query = 'delete from ' . $purchaseTable . ' where supply_id=' . $postData['data']['supply']['supply_id'];
                Db::execute($query);
            }
        }
        if (isset($purchase_id) && isset($purchasePayId) &&
            ($postData['data']['ttl_sum'] == $postData['pay']['paid_sum'])
        ) {
            $rs = Db::table($purchaseTable)->delete($purchase_id);
            $rs = Db::table($purchaseTable)->delete($purchasePayId);
            if (!$rs) json_encode(['result' => 2]);
        }
        return json_encode(['result' => 1]);
    }

    public function getPrice()
    {
        $purchasePriceTable = $this->mdb . '.purchase_price';
        $rs = Db::table($purchasePriceTable)
            ->where($_GET)
            ->find();
        return json_encode(['result' => $rs['price']]);
    }

    public function purchaseList()
    {
        $purchaseTable = $this->mdb . '.purchase_all';
        $supplyTable = $this->mdb . '.supply b';
        $where['type'] = ['in', 'S,B'];
        if ($_GET['search']) {
            $where['summary|supply_sn|supply_name'] = ['like', '%' . $_GET['search'] . '%'];
        }
        if ($_GET['edate'] && $_GET['sdate'])
            $where['time'] = ['between', array($_GET['sdate'], $_GET['edate'])];
        $count = Db::table($purchaseTable)->alias('a')
            ->join($supplyTable, 'a.supply_id=b.supply_id')
            ->where($where)
            ->count();
        if ($count == 0) {
            $purchaseDetailTable = $this->mdb . '.purchase_detail d';
            $goodsTable = $this->mdb . '.goods c';
            unset($where['summary|supply_sn|supply_name']);
            $var=explode("|",$_GET['search']);
            if(sizeof($var)>1) $where['b.supply_sn|b.supply_name'] = ['like', '%' . $var[1] . '%'];
            $where['goods_sn|goods_name'] = ['like', '%' . $var[0] . '%'];
            $rs = Db::table($purchaseTable)->alias('a')
                ->join($purchaseDetailTable, 'a.purchase_id=d.purchase_id')
                ->join($goodsTable, 'd.goods_id=c.goods_id')
                ->join($supplyTable, 'a.supply_id=b.supply_id')
                ->where($where)
                ->field('a.*,b.supply_name')
                ->order('time desc')
                ->page($_GET['page'], 10)
                ->select();
            $count = Db::table($purchaseTable)->alias('a')
                ->join($purchaseDetailTable, 'a.purchase_id=d.purchase_id')
                ->join($goodsTable, 'd.goods_id=c.goods_id')
                ->join($supplyTable, 'a.supply_id=b.supply_id')
                ->where($where)
                ->count();
        } else {
            $rs = Db::table($purchaseTable)->alias('a')
                ->join($supplyTable, 'a.supply_id=b.supply_id')
                ->where($where)
                ->field('a.time,a.purchase_id,a.sum,a.summary,a.inorder,a.user,b.supply_name,b.supply_sn,a.finish')
                ->order('time desc')
                ->page($_GET['page'], 10)
                ->select();
        }

        $data['lists'] = $rs;
        $data['total_count'] = $count;
        return json_encode($data);
    }

    public function deletePurchase()
    {
        $deleteTime=date("Y-m-d H:i:s");
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $purchaseTable = $this->mdb . '.purchase';
        $inorder=Db::table($purchaseTable)->find($postData['purchase_id']);
        //已经开始销售的不能删除
        if($inorder['inorder']!="0") {
            //echo "hello";
            $saleTable = $this->mdb . '.sale_detail';
            $rs = Db::table($saleTable)->where(['inorder' => $inorder['inorder']])->find();
            if ($rs) return json_encode(['result' => 2]);
        }
        $purchaseDetailTable = $this->mdb . '.purchase_detail';
        $inorder=$inorder['inorder'];
        $rs=Db::table($purchaseDetailTable)->where(['purchase_id'=>$postData['purchase_id']])->select();
        $goodsTable=$this->mdb.'.goods';
        $stockTable=$this->mdb.'.stock';
        $unit_priceTable=$this->mdb.'.unit_price';
        $stock_detailTable=$this->mdb.'.stock_detail';
        foreach ($rs as $value){
            $data['inorder']=$inorder;
            $data['goods_id']=$value['goods_id'];
            $data['store_id']=$value['store_id'];
            $stock=Db::table($stockTable)->where($data)->find();
            $good=Db::table($goodsTable)->find($value['goods_id']);
            if($good['unit_id']!=$value['unit_id']){
                $uWhere['goods_id']=$value['goods_id'];
                $uWhere['unit_id']=$good['unit_id'];
                $unit_price=Db::table($unit_priceTable)->where($uWhere)->find();
                $data['Dstock']=(-1)*$value['number']/$unit_price['fx'];
            }else $data['Dstock']=(-1)*$value['number'];
            $data['Dsum']=(-1)*$value['sum'];
            $data['sum']=$data['Dsum']+$stock['sum'];
            $data['stock']=$data['Dstock']+$stock['number'];
            $data['time']=$deleteTime;
            $data['user']=$this->user;
            $data['remark']="删除进货单:".$rs['time'].'-'.$good['goods_name']."-".$value['number'].'*'.$value['price'].'='.$value['sum'];
            $rs=Db::table($stock_detailTable)->insert($data);
            if($rs) $rs=Db::table($purchaseDetailTable)->delete($value['purchase_detail_id']);
            unset($data);
        }
        $rs = Db::table($purchaseTable)
            ->delete($postData['purchase_id']);
        if ($rs) {
            $purchaseTable = $this->mdb . '.purchase_finish';
            $rs = Db::table($purchaseTable)
                ->delete($postData['purchase_id']);
            mylog($this->user, $this->mdb, '删除进货单' . $postData['info']);
            if ($rs) return json_encode(['result' => 1]);
        }
        return json_encode(['result' => 0]);
    }

    public function purchaseDetail()
    {
        $purchaseDetailTable = $this->mdb . '.purchase_detail';
        $goodsTable = $this->mdb . '.goods';
        $storeTable=$this->mdb.'.store c';
        $unitTable=$this->mdb.'.unit d';
        $rs = Db::table($purchaseDetailTable)->alias('a')
            ->join($goodsTable . ' b', 'a.goods_id=b.goods_id')
            ->join($storeTable,'c.store_id=a.store_id')
            ->join($unitTable,'d.unit_id=a.unit_id')
            ->field('goods_name,number,price,sum,purchase_detail_id,purchase_id,store_name,unit_name,b.unit_id,a.goods_id')
            ->where($_GET)
            ->select();
        return json_encode($rs);
    }

    public function purchaseDetailUpdate()
    {
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $goods_unit_id=$postData['goods_unit_id'];
        unset($postData['goods_unit_id']);
        $purchaseDetailTable = $this->mdb . '.purchase_detail';
        $origin=Db::table($purchaseDetailTable)->find($postData['purchase_detail_id']);
        $rs = Db::table($purchaseDetailTable)
            ->update($postData);
        if ($rs) {
            $purchaseTable = $this->mdb . '.purchase';
            $inorder=Db::table($purchaseTable)->find(['purchase_id'=>$postData['purchase_id']]);
            $data['inorder']=$inorder['inorder'];
            $data['goods_id']=$origin['goods_id'];
            $data['store_id']=$origin['store_id'];
            $stockTable=$this->mdb.'.stock';
            $stock=Db::table($stockTable)->where($data)->find();
            if(($origin['number']!=$postData['number'])&&($goods_unit_id!=$origin['unit_id'])){
                $unit_priceTable=$this->mdb.'.unit_price';
                $uWhere['goods_id']=$origin['goods_id'];
                $uWhere['unit_id']=$origin['unit_id'];
                $unit_price=Db::table($unit_priceTable)->where($uWhere)->find();
                $data['Dstock']=($postData['number']-$origin['number'])/$unit_price['fx'];
            }else  $data['Dstock']=$postData['number']-$origin['number'];
            $data['Dsum']=$postData['sum']-$origin['sum'];
            $data['user']=$this->user;
            $data['time']=date("Y-m-d H:i:s");
            $data['stock']=$data['Dstock']+$stock['number'];
            $data['sum']=$data['Dsum']+$stock['sum'];
            $data['remark']="修改进货明细单:".$inorder['time'].'-'.$origin['number'].'*'.$origin['price'].'='.$origin['sum'];
            $stockDetailTable=$this->mdb.'.stock_detail';
            $rs=Db::table($stockDetailTable)->insert($data);

            $sum = Db::table($purchaseDetailTable)
                ->where('purchase_id', $postData['purchase_id'])
                ->sum('sum');

            $rs = Db::table($purchaseTable)
                ->where(['purchase_id' => $postData['purchase_id']])
                ->update(['sum' => $sum, 'summary' => $this->user . '修改于：' . date("Y-m-d H:i:s")]);
            if ($rs) return json_encode(['result' => 1]);
        }
        return json_encode(['result' => 0]);
    }

    public function purchaseDetailDelete()
    {
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $purchaseDetailTable = $this->mdb . '.purchase_detail';
        $purchaseTable = $this->mdb . '.purchase';
        $inorder=Db::table($purchaseTable)->find($postData['purchase_id']);
        $data['inorder']=$inorder['inorder'];
        $origin=Db::table($purchaseDetailTable)->find($postData['purchase_detail_id']);
        $rs = Db::table($purchaseDetailTable)->delete($postData['purchase_detail_id']);
        if ($rs) {
            $data['goods_id']=$origin['goods_id'];
            $data['store_id']=$origin['store_id'];
            $stockTable=$this->mdb.'.stock';
            $stock=Db::table($stockTable)->where($data)->find();
            if($postData['goods_unit_id']!=$origin['unit_id']){
                $unit_priceTable=$this->mdb.'.unit_price';
                $uWhere['goods_id']=$origin['goods_id'];
                $uWhere['unit_id']=$origin['unit_id'];
                $unit_price=Db::table($unit_priceTable)->where($uWhere)->find();
                $data['Dstock']=(-1)*$origin['number']/$unit_price['fx'];
            }else  $data['Dstock']=(-1)*$origin['number'];
            $data['Dsum']=(-1)*$origin['sum'];
            $data['user']=$this->user;
            $data['time']=date("Y-m-d H:i:s");
            $data['stock']=$data['Dstock']+$stock['number'];
            $data['sum']=$data['Dsum']+$stock['sum'];
            $data['remark']="删除进货明细单:".$inorder['time'].'-'.$origin['number'].'*'.$origin['price'].'='.$origin['sum'];
            $stockDetailTable=$this->mdb.'.stock_detail';
            $rs=Db::table($stockDetailTable)->insert($data);
            $sum = Db::table($purchaseDetailTable)
                ->where('purchase_id', $postData['purchase_id'])
                ->sum('sum');
            $rs = Db::table($purchaseTable)
                ->where(['purchase_id' => $postData['purchase_id']])
                ->update(['sum' => $sum, 'summary' => $this->user . '修改于：' . date("Y-m-d H:i:s")]);
            if ($rs) return json_encode(['result' => 1]);
        }
    }

    public function credit()
    {
        $purchaseTable = $this->mdb . '.purchase';
        $supplyTable = $this->mdb . '.supply b';
        $rs = Db::table($purchaseTable)->alias('a')
            ->join($supplyTable, 'a.supply_id=b.supply_id')
            ->field('a.supply_id as supply_id,sum(sum) as sum,supply_name')
            ->page($_GET['page'], 10)
            ->group('a.supply_id')
            ->select();
        $data['credit'] = $rs;
        $data['total_count'] = Db::table($purchaseTable)->count('DISTINCT supply_id');
        return json_encode($data);
    }

    public function ttlCredit()
    {
        $purchaseTable = $this->mdb . '.purchase';
        $rs = Db::table($purchaseTable)->sum('sum');
        return $rs;
    }

    public function creditDetail()
    {
        $purchaseTable = $this->mdb . '.purchase';
        $purchaseDetailTable = $this->mdb . '.purchase_detail b';
        $goodsTable = $this->mdb . '.goods c';
        $rs = Db::table($purchaseTable)->alias('a')
            ->join($purchaseDetailTable, 'a.purchase_id=b.purchase_id', 'LEFT')
            ->join($goodsTable, 'b.goods_id=c.goods_id', 'LEFT')
            ->field('goods_name,price,number,b.sum,type,time,a.purchase_id,a.sum as ttl,summary')
            ->order('time')
            ->where($_GET)
            ->select();
        $data['credit'] = $rs;
        $bankTable = $this->mdb . '.bank';
        $rs = Db::table($bankTable)->order('weight desc')->select();
        $data['bank'] = $rs;
        return json_encode($data);
    }

    public function payment()
    {
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $purchaseTable = $this->mdb . '.purchase';
        $time = $insertTime = date("Y-m-d H:i:s");
        //插入purchase表
        $pData['time'] = $time;
        $pData['supply_id'] = $postData['supply']['supply_id'];
        $pData['type'] = 'P';
        $pData['sum'] = (-1) * $postData['pay']['paid_sum'];
        $pData['user'] = $this->user;
        $purchaseID = Db::table($purchaseTable)
            ->insertGetId($pData);
        if (!$purchaseID) return json_encode(['result' => 0]);
        //插入bdetail表
        $bankDetailTable = $this->mdb . '.bdetail';
        $bData['time'] = $time;
        $bData['sum'] = (-1) * $postData['pay']['paid_sum'];
        $bData['bank_id'] = $postData['pay']['bank'];
        $bData['summary'] = '付款给供应商：' . $postData['supply']['supply_name'];
        $bData['user'] = $this->user;
        $bData['mytable'] = 'purchase';
        $bankTable = $this->mdb . '.bank';
        $rs = Db::table($bankTable)->find($bData['bank_id']);
        $bData['balance'] = $rs['balance'] + $bData['sum'];
        $rs = Db::table($bankDetailTable)
            ->insert($bData);
        if (!$rs) return json_encode(['result' => 2]);
        //判断purchase表中插入数据是否删除
        $sum = Db::table($purchaseTable)
            ->where(['supply_id' => $postData['supply']['supply_id']])
            ->sum('sum');
        if ($sum == 0) {
            $query = 'delete from ' . $purchaseTable . ' where supply_id = ' . $postData['supply']['supply_id'];
            Db::execute($query);
            return json_encode(['result' => 1]);
        }
        if (sizeof($postData['purchaseID']) > 0) {
            $id = $postData['purchaseID'];
            array_push($id, $purchaseID);
            $sum = Db::table($purchaseTable)
                ->where('purchase_id', 'in', $id)
                ->sum('sum');
            if ($sum == 0) {
                $rs = Db::table($purchaseTable)
                    ->where('purchase_id', 'in', $id)
                    ->delete();
                if ($rs) return json_encode(['result' => 1]);
            }
        }
        return json_encode(['result' => 1]);
    }

    public function carryOver()
    {
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $purchaseTable = $this->mdb . '.purchase';
        $where['supply_id'] = $postData['supply_id'];
        $where['purchase_id'] = ['in', $postData['purchaseID']];
        $time = date("Y-m-d H:i:s");
        $data['time'] = $time;
        $data['supply_id'] = $postData['supply_id'];
        $data['sum'] = Db::table($purchaseTable)->where($where)->sum('sum');
        $data['user'] = $this->user;
        $data['summary'] = $postData['summary'];
        $data['type'] = 'CF';
        $insertID = Db::table($purchaseTable)
            ->insertGetId($data);
        if (!$insertID) return json_encode(['result' => 2]);
        $rs = Db::table($purchaseTable)->where('purchase_id', 'in', $postData['purchaseID'])->update(['cf' => $insertID]);
        if (!$rs) return json_encode(['result' => 2]);
        $data['sum'] = (-1) * $data['sum'];
        $data['type'] = 'BCF';
        $insertBCF = Db::table($purchaseTable)
            ->insertGetId($data);
        if (!$insertBCF) return json_encode(['result' => 2]);
        array_push($postData['purchaseID'], $insertBCF);
        $rs = Db::table($purchaseTable)->where('purchase_id', 'in', $postData['purchaseID'])->delete();
        if (!$rs) return json_encode(['result' => 2]);
        return json_encode(['result' => 1]);
    }

    public function cfPrint()
    {
        $purchaseTable = $this->mdb . '.purchase_finish';
        $purchaseDetailTable = $this->mdb . '.purchase_detail b';
        $goodsTable = $this->mdb . '.goods c';
        $rs = Db::table($purchaseTable)->alias('a')
            ->join($purchaseDetailTable, 'a.purchase_id=b.purchase_id', 'LEFT')
            ->join($goodsTable, 'b.goods_id=c.goods_id', 'LEFT')
            ->field('goods_name,price,number,b.sum,type,time,a.purchase_id,a.sum as ttl,summary')
            ->order('time')
            ->where($_GET)
            ->select();
        $data['credit'] = $rs;
        return json_encode($data);
    }
    public function getInOrder()
    {
        $purchaseTable = $this->mdb . '.purchase_all';
        $inorder='IN'.date("Ymd").'-';
        $where['inorder']=['like', $inorder . '%'];
        $rs=Db::table($purchaseTable)
            ->where($where)
            ->count();
        $i=$rs+1;
        while($rs>0){
            //$i++;
            $where['inorder']=['like', $inorder .$i. '%'];
            $rs=Db::table($purchaseTable)
                ->where($where)
                ->count();
        }
        return json_encode($inorder.$i);
    }
    public function purchaseInfo(){
        $storeTable=$this->mdb.'.store';
        $rs['store']=Db::table($storeTable)->order('store_id')->select();
        $bankTable=$this->mdb.'.bank';
        $rs['bank']=Db::table($bankTable)
            ->order('weight desc')
            ->select();
        $unitTable=$this->mdb.'.unit';
        $rs['units']=Db::table($unitTable)->select();
        return json_encode($rs);

    }
    public function getUnit(){
        $cwhere['goods_id']=$_GET['goods_id'];
        $cwhere['inorder']=$_GET['inorder']==''?['like','IN%']:0;
        $stockTable=$this->mdb.'.stock';
        $rs=Db::table($stockTable)->where($cwhere)->find();
        if($rs){
            return json_encode(['check'=>false]);
        }
        unset($_GET['inorder']);
        $unit_priceTable=$this->mdb.'.unit_price';
        $unitTable=$this->mdb.'.unit b';
        $where['goods_id']=$_GET['goods_id'];
        $where['fx']=['>',0];
        $rs=Db::table($unit_priceTable)->alias('a')
            ->join($unitTable,' a.unit_id=b.unit_id')
            ->where($where)
            ->field('b.unit_id,unit_name')
            ->select();
        $temp=Db::table($unitTable)->find($_GET['unit_id']);
        $rs[sizeof($rs)]=$temp;
        $result['unit']=$rs;
        if($_GET['supply_id']!=0) {
            $purchasePriceTable = $this->mdb . '.purchase_price';
            $rs = Db::table($purchasePriceTable)
                ->where($_GET)
                ->find();
            $result['result']=$rs['price'];
        }

        return json_encode($result);
    }
}
