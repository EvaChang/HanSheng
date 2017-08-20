<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25
 * Time: 7:12
 */

namespace app\index\controller;
use think\Db;

class Finance
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
    public function bank(){
        $bankTable=$this->mdb.'.bank';
        $rs=Db::table($bankTable)
            ->order('weight desc')
            ->select();
        return json_encode($rs);
    }
    public function bankAdd(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $bankTable=$this->mdb.'.bank';
        $rs=Db::table($bankTable)
            ->where(['bank_name'=>$postData['bank_name']])
            ->find();
        if($rs) return json_encode(['result'=>3]);
        $data['bank_name']=$postData['bank_name'];
        $data['balance']=0;
        $rs=Db::table($bankTable)
            ->insertGetId($data);
        if($rs){
            $bdetailTable=$this->mdb.'.bdetail';
            $mydata['bank_id']=$rs;
            $mydata['sum']=$mydata['balance']=$postData['balance'];
            $mydata['time']=date("Y-m-d H:i:s");
            $mydata['summary']="初期新建银行";
            $mydata['user']=$this->user;
            $rs=Db::table($bdetailTable)
                ->insert($mydata);
            if($rs) {
                mylog($this->user, $this->mdb, '添加银行:' . $postData['bank_name']);
                return json_encode(['result' => 1]);
            }
        }
        return json_encode(['result'=>0]);
    }
    public function bankUpdate(){
        $bankTable=$this->mdb.'.bank';
        $query='update '.$bankTable.' set weight=0';
        Db::execute($query);
        $rs=Db::table($bankTable)
            ->where(['bank_id'=>$_GET['bank_id']])
            ->setField(['weight'=>1]);
        if($rs) return json_encode(['result' => 1]);
        return json_encode(['result' => 0]);
    }
    public function bankDetail(){
        $bankDeailTable=$this->mdb.'.bdetail';
        $where['bank_id']=$_GET['bank_id'];
        if($_GET['search']!='')
            $where['summary']=['like','%'.$_GET['search'].'%'];
        if($_GET['edate']&&$_GET['sdate'])
            $where['time']=['between',array($_GET['sdate'],$_GET['edate'])];
        $rs=Db::table($bankDeailTable)
            ->where($where)
            ->order('time desc')
            ->page($_GET['page'].',10')
            ->select();
        $count=Db::table($bankDeailTable)
            ->where($where)
            ->count();
        $data['details']=$rs;
        $data['total_count']=$count;
        return json_encode($data);
    }
    public function transfer(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $bankDetailTable=$this->mdb.'.bdetail';
        $bankTable=$this->mdb.'.bank';
        $summary=isset($postData['trans']['summary'])?$postData['trans']['summary']:'';
        //outBank 插入扣款
        $outBank=Db::table($bankTable)->find($postData['trans']['outBank']);
        $inBank=Db::table($bankTable)->find($postData['trans']['inBank']);
        $data['user']=$this->user;
        $data['time']=date("Y-m-d H:i:s");
        $data['summary']='转到'.$inBank['bank_name'].':'.$summary;
        $data['bank_id']=$postData['trans']['outBank'];
        $data['sum']=(-1)*$postData['trans']['sum'];
        $data['balance']=$data['sum']+$outBank['balance'];
        $rs=Db::table($bankDetailTable)
            ->insert($data);
        if(!$rs) return json_encode(['result'=>0]);
        //inBank 插入付款
        $data['sum']=$postData['trans']['sum'];
        $data['bank_id']=$postData['trans']['inBank'];
        $data['balance']=$data['sum']+$inBank['balance'];
        $data['summary']='由'.$outBank['bank_name'].'转入:'.$summary;
        $rs=Db::table($bankDetailTable)
            ->insert($data);
        if(!$rs) return json_encode(['result'=>0]);
        return json_encode(['result'=>1]);
    }
    public function subjectUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $subjectTable=$this->mdb.'.subject';
        if($postData['subject_id']==0){
            unset($postData['subject_id']);
            $rs=Db::table($subjectTable)->insert($postData);
        }else{
            $rs=Db::table($subjectTable)->update($postData);
        }
        if(!$rs) return json_encode(['result'=>0]);
        return json_encode(['result'=>1]);

    }

    public function subject(){
        $subjectTable=$this->mdb.'.subject';
        $rs=Db::table($subjectTable)
            ->page($_GET['page'],10)
            ->select();
        $count=Db::table($subjectTable)->count();
        $data['total_count']=$count;
        $data['subject']=$rs;
        return json_encode($data);

    }
    public function  bankAndSubject(){
        $subjectTable=$this->mdb.'.subject';
        $where['subject_id']=['gt',2];
        $rs=Db::table($subjectTable)
            ->where($_GET)
            ->where($where)
            ->select();
        $data['subjects']=$rs;
        $bankTable=$this->mdb.'.bank';
        $rs=Db::table($bankTable)
            ->order('weight desc')
            ->select();
        $data['banks']=$rs;
        return json_encode($data);

    }
    public function peopleSearch(){
        $member=$this->mdb.'.member';
        $supply=$this->mdb.'.supply';
        $where['member_sn|member_name']=['like','%'.$_GET['search'].'%'];
        $rs=Db::table($member)
            ->field("member_name as name,member_id as id,'member' as mytable")
            ->where($where)
            ->limit(7)
            ->select();
        unset($where);
        $where['supply_sn|supply_name']=['like','%'.$_GET['search'].'%'];
        $rs1=Db::table($supply)
            ->field("supply_name as name,supply_id as id,'supply' as mytable")
            ->where($where)
            ->limit(7)
            ->select();
        return json_encode(array_merge($rs, $rs1));
    }
    public function incomeAdd(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $time=date("Y-m-d H:i:s");
        $accountTable=$this->mdb.'.account';
        $subjectTable=$this->mdb.'.subject';
        $subject=Db::table($subjectTable)->find($postData['subject']);
        $str=$postData['people']==''?($postData['people']['mytable']=='member'?'客户:':'供应商:').$postData['people']['name']:'现金';
        if($postData['bank_id']==0){
            if(!isset($postData['people'])) return json_encode(['result'=>0]);
            //没有付款，录入收入条目
            //插入应收应付
            $data['time']=$time;
            $data['user']=$this->user;
            $table=$postData['people']['mytable'];
            $adata['creditTable']=$table=='supply'?'purchase':'sale';
            $data['sum']=$table=='member'?$postData['income']:(-1)*$postData['income'];
            $data[$table.'_id']=$postData['people']['id'];
            $data['type']='income';
            $data['summary']=$str.'收入条目-'.$subject['subject_name'].':'.$postData['summary'];
            $table=$this->mdb.'.'.$adata['creditTable'];
            $rs=Db::table($table)->insertGetId($data);
            if(!$rs) return json_encode(['result'=>0]);
            //插入account表
            $adata['time']=$time;
            $adata['user']=$this->user;
            $adata['income']=$postData['income'];
            $adata['subject']=$postData['subject'];
            $adata['sale_detail_id']=$rs;
            $adata['summary']=$str.':'.$postData['summary'];
            $rs=Db::table($accountTable)->insert($adata);
            if(!$rs) return json_encode(['result'=>0]);
            return json_encode(['result'=>1]);
        }else{
            ////插入account表
            $adata['summary']=$str.':'.$postData['summary'];
            $adata['time']=$time;
            $adata['user']=$this->user;
            $adata['income']=$postData['income'];
            $adata['subject']=$postData['subject'];
            $rs=Db::table($accountTable)->insert($adata);
            if(!$rs) return json_encode(['result'=>0]);
            //插入bdetail表
            $bankTable=$this->mdb.'.bank';
            $bdtail=$this->mdb.'.bdetail';
            $balance=Db::table($bankTable)->find($postData['bank_id']);
            $data['balance']=$postData['income']+$balance['balance'];
            $data['time']=$time;
            $data['user']=$this->user;
            $data['bank_id']=$postData['bank_id'];
            $data['summary']=$str.':收入条目-'.$subject['subject_name'].':'.$postData['summary'];
            $data['mytable']='account';
            $data['sum']=$postData['income'];
            $rs=Db::table($bdtail)->insert($data);
            if(!$rs) return json_encode(['result'=>0]);
            return json_encode(['result'=>1]);
        }
    }
    public function income(){
        $accountTable=$this->mdb.'.account';
        $subjectTable=$this->mdb.'.subject b';
        $where['summary']=['like','%'.$_GET['search'].'%'];
        $where['subject']=['gt',2];
        $rs=Db::table($accountTable)->alias('a')
            ->join($subjectTable,'a.subject=b.subject_id and b.subject_type=0')
            ->where($where)
            ->order('time desc')
            ->page($_GET['page'],10)
            ->select();
        $i=0;
        foreach ($rs as $value) {
            $rs[$i]['editing']=0;
            if($value['creditTable']!=null) {
                $table = $this->mdb . '.' . $value['creditTable'];
                $res = Db::table($table)->find($value['sale_detail_id']);
                $rs[$i]['editing']=sizeof($res);
            }
                $i++;
        }
        $data['incomes']=$rs;
        $count=Db::table($accountTable)->alias('a')
            ->join($subjectTable,'a.subject=b.subject_id and b.subject_type=0')
            ->where($where)
            ->count();
        $data['total_count']=$count;
        return json_encode($data);
    }
    public function deleteIncome(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        if($postData['creditTable']==null) return json_encode(['result'=>0]);
        $table=$this->mdb.'.'.$postData['creditTable'];
        $where[$postData['creditTable'].'_id']=$postData['sale_detail_id'];
        $rs=Db::table($table)->where($where)->delete();
        if(!$rs) return json_encode(['result'=>0]);
        $accountTable=$this->mdb.'.account';
        unset($where);
        $where['account_id']=$postData['account_id'];
        $rs=Db::table($accountTable)->where($where)->delete();
        if(!$rs) return json_encode(['result'=>0]);
        mylog($this->user,$this->mdb,$postData['summary'].';金额：'.$postData['income']);
        return json_encode(['result'=>1]);
    }
    public function incomeUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $accountTable=$this->mdb.'.account';
        $data=$postData;
        $rs=Db::table($accountTable)->find($postData['account_id']);
        //update credit
        if($rs['income']!=$postData['income']) {
            $table = $this->mdb . '.' . $rs['creditTable'];
            $sdata[$rs['creditTable'].'_id']=$rs['sale_detail_id'];
            $sdata['sum']=$rs['creditTable']=='sale'?$postData['income']:$postData['income']*(-1);
            $srs=Db::table($table)->update($sdata);
            if(!$srs) return json_encode(['result'=>0]);
        }
        //update account
        $tempCheck=explode($rs['summary'],$postData['summary']);
        if(sizeof($tempCheck)>0) {
            $data['summary'] = $postData['summary'] . ','.$this->user . '修改于' . $time = date('Y-m-d H:i:s');
        }else{
            $str=explode(':',$rs['summary']);
           $data['summary']=$str[0].$str[1].$postData['summary']. ','.$this->user . '修改于' . $time = date('Y-m-d H:i:s');
        }
        $rs=Db::table($accountTable)->update($data);
        if(!$rs) return json_encode(['result'=>0]);
        return json_encode(['result'=>1]);

    }
    
    //支出
    public function costAdd(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $time=date("Y-m-d H:i:s");
        $accountTable=$this->mdb.'.account';
        $subjectTable=$this->mdb.'.subject';
        $subject=Db::table($subjectTable)->find($postData['subject']);
        $str=$postData['people']==''?($postData['people']['mytable']=='member'?'客户:':'供应商:').$postData['people']['name']:'现金';
        if($postData['bank_id']==0){
            if(!isset($postData['people'])) return json_encode(['result'=>0]);
            //没有付款，录入支出条目
            //插入应收应付
            $data['time']=$time;
            $data['user']=$this->user;
            $table=$postData['people']['mytable'];
            $adata['creditTable']=$table=='supply'?'purchase':'sale';
            $data['sum']=$table=='supply'?$postData['cost']:(-1)*$postData['cost'];
            $data[$table.'_id']=$postData['people']['id'];
            $data['type']='cost';
            $data['summary']=$str.'支出条目-'.$subject['subject_name'].':'.$postData['summary'];
            $table=$this->mdb.'.'.$adata['creditTable'];
            $rs=Db::table($table)->insertGetId($data);
            if(!$rs) return json_encode(['result'=>0]);
            //插入account表
            $adata['time']=$time;
            $adata['user']=$this->user;
            $adata['cost']=$postData['cost'];
            $adata['subject']=$postData['subject'];
            $adata['sale_detail_id']=$rs;
            $adata['summary']=$str.':'.$postData['summary'];
            $rs=Db::table($accountTable)->insert($adata);
            if(!$rs) return json_encode(['result'=>0]);
            return json_encode(['result'=>1]);
        }else{
            ////插入account表
            $adata['summary']=$str.':'.$postData['summary'];
            $adata['time']=$time;
            $adata['user']=$this->user;
            $adata['cost']=$postData['cost'];
            $adata['subject']=$postData['subject'];
            $rs=Db::table($accountTable)->insert($adata);
            if(!$rs) return json_encode(['result'=>0]);
            //插入bdetail表
            $bankTable=$this->mdb.'.bank';
            $bdtail=$this->mdb.'.bdetail';
            $balance=Db::table($bankTable)->find($postData['bank_id']);
            $data['balance']=$balance['balance']-$postData['cost'];
            $data['time']=$time;
            $data['user']=$this->user;
            $data['bank_id']=$postData['bank_id'];
            $data['summary']=$str.':支出条目-'.$subject['subject_name'].':'.$postData['summary'];
            $data['mytable']='account';
            $data['sum']=$postData['cost']*(-1);
            $rs=Db::table($bdtail)->insert($data);
            if(!$rs) return json_encode(['result'=>0]);
            return json_encode(['result'=>1]);
        }
    }
    public function cost(){
        $accountTable=$this->mdb.'.account';
        $subjectTable=$this->mdb.'.subject b';
        $where['summary']=['like','%'.$_GET['search'].'%'];
        $where['subject']=['gt',2];
        $rs=Db::table($accountTable)->alias('a')
            ->join($subjectTable,'a.subject=b.subject_id and b.subject_type=1')
            ->where($where)
            ->order('time desc')
            ->page($_GET['page'],10)
            ->select();
        $i=0;
        foreach ($rs as $value) {
            $rs[$i]['editing']=0;
            if($value['creditTable']!=null) {
                $table = $this->mdb . '.' . $value['creditTable'];
                $res = Db::table($table)->find($value['sale_detail_id']);
                $rs[$i]['editing']=sizeof($res);
            }
            $i++;
        }
        $data['costs']=$rs;
        $count=Db::table($accountTable)->alias('a')
            ->join($subjectTable,'a.subject=b.subject_id and b.subject_type=1')
            ->where($where)
            ->count();
        $data['total_count']=$count;
        return json_encode($data);
    }
    public function deletecost(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        if($postData['creditTable']==null) return json_encode(['result'=>0]);
        $table=$this->mdb.'.'.$postData['creditTable'];
        $where[$postData['creditTable'].'_id']=$postData['sale_detail_id'];
        $rs=Db::table($table)->where($where)->delete();
        if(!$rs) return json_encode(['result'=>0]);
        $accountTable=$this->mdb.'.account';
        unset($where);
        $where['account_id']=$postData['account_id'];
        $rs=Db::table($accountTable)->where($where)->delete();
        if(!$rs) return json_encode(['result'=>0]);
        mylog($this->user,$this->mdb,$postData['summary'].';金额：'.$postData['cost']);
        return json_encode(['result'=>1]);
    }
    public function costUpdate(){
        $postData=file_get_contents("php://input",true);
        $postData=json_decode($postData,true);
        $accountTable=$this->mdb.'.account';
        $data=$postData;
        $rs=Db::table($accountTable)->find($postData['account_id']);
        //update credit
        if($rs['cost']!=$postData['cost']) {
            $table = $this->mdb . '.' . $rs['creditTable'];
            $sdata[$rs['creditTable'].'_id']=$rs['sale_detail_id'];
            $sdata['sum']=$rs['creditTable']=='sale'?$postData['cost']:$postData['cost']*(-1);
            $srs=Db::table($table)->update($sdata);
            if(!$srs) return json_encode(['result'=>0]);
        }
        //update account
        $tempCheck=explode($rs['summary'],$postData['summary']);
        if(sizeof($tempCheck)>0) {
            $data['summary'] = $postData['summary'] . ','.$this->user . '修改于' . $time = date('Y-m-d H:i:s');
        }else{
            $str=explode(':',$rs['summary']);
            $data['summary']=$str[0].$str[1].$postData['summary']. ','.$this->user . '修改于' . $time = date('Y-m-d H:i:s');
        }
        $rs=Db::table($accountTable)->update($data);
        if(!$rs) return json_encode(['result'=>0]);
        return json_encode(['result'=>1]);

    }


}