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
        //插入purchase表
        if (sizeof($postData['detail']) > 0) {
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
            //插入purchaseDetail 表
            $detail = [];
            $i = 0;
            foreach ($postData['detail'] as $value) {
                $detail[$i]['purchase_id'] = $purchase_id;
                $detail[$i]['goods_id'] = $value['good']['goods_id'];
                $detail[$i]['number'] = $value['number'] * $back;
                $detail[$i]['price'] = $value['price'];
                $detail[$i]['sum'] = $value['sum'] * $back;
                $detail[$i]['store_id'] = $value['store_id'];
                $i++;
            }
            $purchaseDetailTable = $this->mdb . '.purchase_detail';
            $rs = Db::table($purchaseDetailTable)
                ->insertAll($detail);
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
            $where['goods_sn|goods_name'] = ['like', '%' . $_GET['search'] . '%'];
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
                ->where($where)
                ->count();
        } else {
            $rs = Db::table($purchaseTable)->alias('a')
                ->join($supplyTable, 'a.supply_id=b.supply_id')
                ->where($where)
                ->field('a.time,a.purchase_id,a.sum,a.summary,a.user,b.supply_name,b.supply_sn,a.finish')
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
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $purchaseDetailTable = $this->mdb . '.purchase_detail';
        $purchaseTable = $this->mdb . '.purchase';
        $query = 'delete from ' . $purchaseDetailTable . ' where purchase_id=' . $postData['purchase_id'];
        Db::execute($query);
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
        $rs = Db::table($purchaseDetailTable)->alias('a')
            ->join($goodsTable . ' b', 'a.goods_id=b.goods_id')
            ->field('goods_name,number,price,sum,purchase_detail_id,purchase_id')
            ->where($_GET)
            ->select();
        return json_encode($rs);
    }

    public function purchaseDetailUpdate()
    {
        $postData = file_get_contents("php://input", true);
        $postData = json_decode($postData, true);
        $purchaseDetailTable = $this->mdb . '.purchase_detail';
        $rs = Db::table($purchaseDetailTable)
            ->update($postData);
        if ($rs) {
            $sum = Db::table($purchaseDetailTable)
                ->where('purchase_id', $postData['purchase_id'])
                ->sum('sum');
            $purchaseTable = $this->mdb . '.purchase';
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
        $rs = Db::table($purchaseDetailTable)->delete($postData['purchase_detail_id']);
        if ($rs) {
            $sum = Db::table($purchaseDetailTable)
                ->where('purchase_id', $postData['purchase_id'])
                ->sum('sum');
            $purchaseTable = $this->mdb . '.purchase';
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
        $purchaseTable = $this->mdb . '.purchase_detail';
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
}