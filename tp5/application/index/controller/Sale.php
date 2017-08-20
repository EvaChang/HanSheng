<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/22 0022
 * Time: 下午 1:11
 */

namespace app\index\controller;
use think\Db;

class Sale
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
    public function member(){
        $memberTable=$this->mdb.'.member';
        $rs=Db::table($memberTable)
            ->where('member_name','like','%'.$_GET['search'].'%')
            ->whereOr('member_sn','like','%'.$_GET['search'].'%')
            ->page($_GET['page'].',10')
            ->select();
        $count=Db::table($memberTable)
            ->where('member_name','like','%'.$_GET['search'].'%')
            ->whereOr('member_sn','like','%'.$_GET['search'].'%')
            ->count();
        $data['member']=$rs;
        $data['total_count']=$count;
        return json_encode($data);
    }
    public function memberUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $memberTable=$this->mdb.'.member';
        if($postData['member_id']==''){
            $rs=Db::table($memberTable)
                ->where(['member_name'=>$postData['member_name']])
                ->find();
            if($rs) return json_encode(['result'=>3]);
            $rs=Db::table($memberTable)
                ->insert($postData);
            if($rs) return json_encode(['result'=>1]);
        }else{
            $data['member_id']=$postData['member_id'];
            $data['member_sn']=$postData['member_sn'];
            $data['member_name']=$postData['member_name'];
            $data['member_phone']=$postData['member_phone'];
            $data['vip']=$postData['vip'];
            $rs=Db::table($memberTable)
                ->update($data);
            if($rs) return json_encode(['result'=>1]);
        }
        return json_encode(['result'=>0]);
    }
    public function memberSearch()
    {
        $memberTable = $this->mdb . '.member';
        $rs = Db::table($memberTable)
            ->where('member_name', 'like', '%' . $_GET['search'] . '%')
            ->whereOr('member_sn', 'like', '%' . $_GET['search'] . '%')
            ->field('member_id,member_name,vip')
            ->page($_GET['page'] . ',10')
            ->select();
        $data['member'] = $rs;
        return json_encode($data);
    }
    public function sale(){
        $sale['summary']="";
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $back=$postData['back'];
        $insertTime=date("Y-m-d H:i:s");
        $saleTable=$this->mdb.'.sale';
        //插入sale表
        if(sizeof($postData['detail'])>0) {
            $sale['member_id']=$postData['data']['member']['member_id'];
            $sale['sum']=$postData['data']['ttl_sum']*$back;
            if(isset($postData['data']['summary'])) $sale['summary'].=$postData['data']['summary'];
            if(isset($postData['data']['on_way'])) $sale['on_way']=1;
            $sale['time']=$insertTime;
            if($back==-1) $sale['summary']='退货：'.$sale['summary'];
            $sale['type']=$back==1?'S':'B';     //S--一般进货销售记录，P--付款记录，CF--结转
            $sale['user']=$this->user;
            $sale_id=Db::table($saleTable)
                ->insertGetId($sale);
            if(!$sale_id) return json_encode(['result'=>0]);
            //插入saleDetail 表
            $detail = [];
            $i = 0;
            foreach ($postData['detail'] as $value) {
                $detail[$i]['sale_id'] = $sale_id;
                $detail[$i]['goods_id'] = $value['good']['goods_id'];
                $detail[$i]['number'] = $value['number']*$back;
                $detail[$i]['price'] = $value['price'];
                $detail[$i]['sum'] = $value['sum'] * $back;
                $i++;
            }
            $saleDetailTable = $this->mdb . '.sale_detail';
            $rs = Db::table($saleDetailTable)
                ->insertAll($detail);
            if (!$rs) return json_encode(['result' => 2]);
        }
        //插入bankDetail表中
        if(isset($postData['pay']['paid_sum'])&&($postData['pay']['paid_sum']!=0)) {
            $pay['sum'] = $postData['pay']['paid_sum'] * $back ;
            $pay['bank_id'] = $postData['pay']['bank'];
            $pay['time'] = $insertTime;
            $string = $back == 1 ? '客户付款：' : '退货，客户退款:';
            $pay['summary'] = $string . $postData['data']['member']['member_name'];
            $pay['user'] = $this->user;
            $pay['mytable'] = 'sale';
            $bankTable = $this->mdb . '.bank';
            $rs = Db::table($bankTable)->find($pay['bank_id']);
            $pay['balance'] = $rs['balance'] + $pay['sum'];
            $bankDetailTable = $this->mdb . '.bdetail';
            $rs = Db::table($bankDetailTable)
                ->insert($pay);
            if (!$rs) return json_encode(['result' => 2]);
            //在sale表中，插入付款信息。
            $salePay['member_id'] = $postData['data']['member']['member_id'];
            $salePay['sum'] = $pay['sum'] * $back*(-1);
            $salePay['time'] = $insertTime;
            $salePay['type'] = 'P';
            $salePay['user'] = $this->user;
            $salePayId = Db::table($saleTable)
                ->insertGetId($salePay);
            if (!$salePayId) return json_encode(['result' => 2]);
            //该member_id 的sum(sum)是否为0，为0则删除。
            $sum = Db::table($saleTable)
                ->where(['member_id' => $postData['data']['member']['member_id']])
                ->sum('sum');
            if ($sum == 0) {
                $query = 'update ' . $saleTable . ' set cf=' . $salePayId . ' where member_id=' . $postData['data']['member']['member_id'] . ' and sale_id <>' . $salePayId;
                Db::execute($query);
                $query = 'delete from ' . $saleTable . ' where member_id=' . $postData['data']['member']['member_id'];
                Db::execute($query);
            }
        }
        if(isset($sale_id)&& isset($salePayId)&&
            ($postData['data']['ttl_sum']==$postData['pay']['paid_sum'])
        ){
            $rs=Db::table($saleTable)->delete($sale_id);
            $rs=Db::table($saleTable)->delete($salePayId);
            if(!$rs) json_encode(['result'=>2]);
        }
        return json_encode(['result'=>1]);
    }
    public function getPrice(){
        $salePriceTable=$this->mdb.'.sale_price';
        $rs=Db::table($salePriceTable)
            ->where($_GET)
            ->find();
        if(!$rs){
            $goodsTable=$this->mdb.'.goods';
            $rs=Db::table($goodsTable)->field('out_price as price,promote')->find($_GET['goods_id']);
            if($rs['promote']==1){
                $memberTable=$this->mdb.'.member';
                $vip=Db::table($memberTable)->field('vip')->find($_GET['member_id']);
                $rs['price']=$rs['price']*$vip['vip'];
            }

        }
        $data['result']=$rs['price'];
        $stockTable=$this->mdb.'.stock';
        $rs=Db::table($stockTable)
            ->where(['goods_id'=>$_GET['goods_id']])
            ->find();
        $data['stock']=$rs['number'];
        return json_encode($data);
    }
    public function getMemberCredit(){
        $saleTable=$this->mdb.'.sale';
            $credit=Db::table($saleTable)
                ->where(['member_id'=>$_GET['member_id']])
                ->sum('sum');
            return json_encode(['credit'=>$credit]);
    }
    public function saleList(){
        $saleTable=$this->mdb.'.sale_all';
        $memberTable=$this->mdb.'.member b';
        $where['type']=['in','S,B'];
        if($_GET['search']){
            $where['summary|member_sn|member_name']=['like','%'.$_GET['search'].'%'];
        }
        if($_GET['edate']&&$_GET['sdate'])
            $where['time']=['between',array($_GET['sdate'],$_GET['edate'])];
        $count=Db::table($saleTable)->alias('a')
            ->join($memberTable,'a.member_id=b.member_id')
            ->where($where)
            ->count();
        if($count==0){
            $saleDetailTable=$this->mdb.'.sale_detail d';
            $goodsTable=$this->mdb.'.goods c';
            unset($where['summary|member_sn|member_name']);
            $where['goods_sn|goods_name']=['like','%'.$_GET['search'].'%'];
            $rs=Db::table($saleTable)->alias('a')
                ->join($saleDetailTable,'a.sale_id=d.sale_id')
                ->join($goodsTable,'d.goods_id=c.goods_id')
                ->join($memberTable,'a.member_id=b.member_id')
                ->where($where)
                ->field('a.*,b.member_name')
                ->order('time desc')
                ->page($_GET['page'],10)
                ->select();
            $count=Db::table($saleTable)->alias('a')
                ->join($saleDetailTable,'a.sale_id=d.sale_id')
                ->join($goodsTable,'d.goods_id=c.goods_id')
                ->where($where)
                ->count();
        }else{
            $rs=Db::table($saleTable)->alias('a')
                ->join($memberTable,'a.member_id=b.member_id')
                ->where($where)
                ->field('a.time,a.sale_id,a.sum,a.summary,a.user,b.member_name,b.member_sn,a.finish')
                ->order('time desc')
                ->page($_GET['page'],10)
                ->select();
        }
        $data['lists']=$rs;
        $data['total_count']=$count;
        return json_encode($data);
    }
    public function deleteSale(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $saleDetailTable=$this->mdb.'.sale_detail';
        $saleTable=$this->mdb.'.sale';
        $query='delete from '.$saleDetailTable.' where sale_id='.$postData['sale_id'];
        Db::execute($query);
        $rs=Db::table($saleTable)
            ->delete($postData['sale_id']);
        if($rs) {
            $saleTable = $this->mdb . '.sale_finish';
            $rs=Db::table($saleTable)
                ->delete($postData['sale_id']);
            mylog($this->user,$this->mdb,'删除销售单'.$postData['info']);
            if($rs) return json_encode(['result'=>1]);
        }
        return json_encode(['result'=>0]);
    }

    public function saleDetail(){
        $saleDetailTable=$this->mdb.'.sale_detail';
        $goodsTable=$this->mdb.'.goods';
        $rs=Db::table($saleDetailTable)->alias('a')
            ->join($goodsTable.' b','a.goods_id=b.goods_id')
            ->field('goods_name,number,price,sum,unit,sale_detail_id,sale_id')
            ->where($_GET)
            ->select();
        return json_encode($rs);
    }
    public function saleDetailUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $saleDetailTable=$this->mdb.'.sale_detail';
        $rs=Db::table($saleDetailTable)
            ->update($postData);
        if($rs){
            $sum=Db::table($saleDetailTable)
                ->where('sale_id',$postData['sale_id'])
                ->sum('sum');
            $saleTable=$this->mdb.'.sale';
            $rs=Db::table($saleTable)
                ->where(['sale_id'=>$postData['sale_id']])
                ->update(['sum'=>$sum,'summary'=>$this->user.'修改于：'.date("Y-m-d H:i:s")]);
            if($rs) return json_encode(['result'=>1]);
        }
        return json_encode(['result'=>0]);
    }
    public function saleDetailDelete(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $saleDetailTable=$this->mdb.'.sale_detail';
        $rs=Db::table($saleDetailTable)->delete($postData['sale_detail_id']);
        if($rs)
        {
            $sum=Db::table($saleDetailTable)
                ->where('sale_id',$postData['sale_id'])
                ->sum('sum');
            $saleTable=$this->mdb.'.sale';
            $rs=Db::table($saleTable)
                ->where(['sale_id'=>$postData['sale_id']])
                ->update(['sum'=>$sum,'summary'=>$this->user.'修改于：'.date("Y-m-d H:i:s")]);
            if($rs) return json_encode(['result'=>1]);
        }
    }

    public function credit(){
        $saleTable=$this->mdb.'.sale';
        $memberTable=$this->mdb.'.member b';
        $rs=Db::table($saleTable)->alias('a')
            ->join($memberTable,'a.member_id=b.member_id')
            ->field('a.member_id as member_id,sum(sum) as sum,member_name')
            ->page($_GET['page'],10)
            ->group('a.member_id')
            ->select();
        $data['credit']=$rs;
        $data['total_count']=Db::table($saleTable)->count('DISTINCT member_id');
        return json_encode($data);
    }
    public function ttlCredit(){
        $saleTable=$this->mdb.'.sale';
        $rs=Db::table($saleTable)->sum('sum');
        return $rs;
    }
    public function creditDetail(){
        $saleTable=$this->mdb.'.sale';
        $saleDetailTable=$this->mdb.'.sale_detail b';
        $goodsTable=$this->mdb.'.goods c';
        $rs=Db::table($saleTable)->alias('a')
            ->join($saleDetailTable,'a.sale_id=b.sale_id','LEFT')
            ->join($goodsTable,'b.goods_id=c.goods_id','LEFT')
            ->field('goods_name,price,number,b.sum,type,time,a.sale_id,a.sum as ttl,summary')
            ->order('time')
            ->where($_GET)
            ->select();
        $data['credit']=$rs;
        $bankTable=$this->mdb.'.bank';
        $rs=Db::table($bankTable)->order('weight desc')->select();
        $data['bank']=$rs;
        return json_encode($data);
    }
    public function payment(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $saleTable=$this->mdb.'.sale';
        $time=$insertTime=date("Y-m-d H:i:s");
        //插入sale表
        $pData['time']=$time;
        $pData['member_id']=$postData['member']['member_id'];
        $pData['type']='P';
        $pData['sum']=(-1)*$postData['pay']['paid_sum'];
        $pData['user']=$this->user;
        $saleID=Db::table($saleTable)
            ->insertGetId($pData);
        if(!$saleID) return json_encode(['result'=>0]);
        //插入bdetail表
        $bankDetailTable=$this->mdb.'.bdetail';
        $bData['time']=$time;
        $bData['sum']=$postData['pay']['paid_sum'];
        $bData['bank_id']=$postData['pay']['bank'];
        $bData['summary']='客户付款：'.$postData['member']['member_name'];
        $bData['user']=$this->user;
        $bData['mytable']='sale';
        $bankTable = $this->mdb . '.bank';
        $rs = Db::table($bankTable)->find($bData['bank_id']);
        $bData['balance']=$rs['balance']+$bData['sum'];
        $rs=Db::table($bankDetailTable)
            ->insert($bData);
        if(!$rs) return json_encode(['result'=>2]);
        //判断sale表中插入数据是否删除
        $sum=Db::table($saleTable)
            ->where(['member_id'=>$postData['member']['member_id']])
            ->sum('sum');
        if($sum==0){
            $query='delete from '.$saleTable. ' where member_id = '.$postData['member']['member_id'];
            Db::execute($query);
            return json_encode(['result'=>1]);
        }
        if(sizeof($postData['saleID'])>0){
            $id=$postData['saleID'];
            array_push($id,$saleID);
            $sum=Db::table($saleTable)
                ->where('sale_id','in',$id)
                ->sum('sum');
            if($sum==0){
                $rs=Db::table($saleTable)
                    ->where('sale_id','in',$id)
                    ->delete();
                if($rs) return json_encode(['result'=>1]);
            }
        }
        return json_encode(['result'=>1]);
    }
    public function carryOver(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $saleTable=$this->mdb.'.sale';
        $where['member_id']=$postData['member_id'];
        $where['sale_id']=['in',$postData['saleID']];
        $time=date("Y-m-d H:i:s");
        $data['time']=$time;
        $data['member_id']=$postData['member_id'];
        $data['sum']=Db::table($saleTable)->where($where)->sum('sum');
        $data['user']=$this->user;
        $data['summary']=$postData['summary'];
        $data['type']='CF';
        $insertID=Db::table($saleTable)
            ->insertGetId($data);
        if(!$insertID) return json_encode(['result'=>2]);
        $rs=Db::table($saleTable)->where('sale_id','in',$postData['saleID'])->update(['cf'=>$insertID]);
        if(!$rs) return json_encode(['result'=>2]);
        $data['sum']=(-1)*$data['sum'];
        $data['type']='BCF';
        $insertBCF=Db::table($saleTable)
            ->insertGetId($data);
        if(!$insertBCF) return json_encode(['result'=>2]);
        array_push($postData['saleID'],$insertBCF);
        $rs=Db::table($saleTable)->where('sale_id','in',$postData['saleID'])->delete();
        if(!$rs) return json_encode(['result'=>2]);
        return json_encode(['result'=>1]);
    }
    public function cfPrint(){
        $saleTable=$this->mdb.'.sale_finish';
        $saleDetailTable=$this->mdb.'.sale_detail b';
        $goodsTable=$this->mdb.'.goods c';
        $where=$_GET;
        $where['type']=['neq','BCF'];
        $rs=Db::table($saleTable)->alias('a')
            ->join($saleDetailTable,'a.sale_id=b.sale_id','LEFT')
            ->join($goodsTable,'b.goods_id=c.goods_id','LEFT')
            ->field('goods_name,price,number,b.sum,type,time,a.sale_id,a.sum as ttl,summary')
            ->order('time')
            ->where($where)
            ->select();
        $data['credit']=$rs;
        return json_encode($data);
    }
    public function printSale(){
       // $saleTable=$this->mdb.'.sale';
        $saleDetailTable=$this->mdb.'.sale_detail b';
        $goodsTable=$this->mdb.'.goods c';
        $rs=Db::table($saleDetailTable)
            ->join($goodsTable,'b.goods_id=c.goods_id','LEFT')
            ->field('goods_name,price,number,unit,sum')
            ->where($_GET)
            ->select();
        $data['credit']=$rs;
        $rs=Db::table($saleDetailTable)
            ->field('sum(number) as number,sum(sum) as sum')
            ->where($_GET)
            ->find();
        $infoTable=$this->mdb.'.info';
        $data['ttl']=$rs;
        $rs=Db::table($infoTable)->find(1);
        $data['info']=$rs;
        return json_encode($data);
    }
    public function printInfo(){
        $infoTable=$this->mdb.'.info';
        $rs=Db::table($infoTable)->find(1);
        $data['info']=$rs;
        return json_encode($data);
    }
     public function printCredit(){
        $saleTable=$this->mdb.'.sale';
        $saleDetailTable=$this->mdb.'.sale_detail b';
        $goodsTable=$this->mdb.'.goods c';
        $rs=Db::table($saleTable)->alias('a')
            ->join($saleDetailTable,'a.sale_id=b.sale_id','LEFT')
            ->join($goodsTable,'b.goods_id=c.goods_id','LEFT')
            ->field('goods_name,price,number,b.sum,type,time,a.sale_id,a.sum as ttl,summary')
            ->order('time')
            ->where($_GET)
            ->select();
        $data['credit']=$rs;
        $rs['sum']=Db::table($saleTable)
           // ->join($saleDetailTable,'a.sale_id=b.sale_id')
           // ->field('sum(number) as number,sum(a.sum) as sum')
            ->where($_GET)
            ->sum('sum');
            //->find();
         $rs['number']=Db::table($saleTable)->alias('a')
              ->join($saleDetailTable,'a.sale_id=b.sale_id')
             ->where($_GET)
             ->sum('number');
        $infoTable=$this->mdb.'.info';
        $data['ttl']=$rs;
        $rs=Db::table($infoTable)->find(1);
        $data['info']=$rs;
        return json_encode($data);
    }




}
