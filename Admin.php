<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: 当燃      
 * Date: 2015-09-09
 */

namespace app\admin\controller;

use app\common\logic\AdminLogic;
use app\common\logic\ModuleLogic;
use think\Page;
use think\Verify;
use think\Loader;
use think\Db;
use think\Session;

class Admin extends Base {

    public function index(){
    	$list = array();
    	$keywords = I('keywords/s');
    	if(empty($keywords)){
    		$res = D('admin')->where('admin_id','not in','2,3')->select();
    	}else{
			$res = DB::name('admin')->where('user_name','like','%'.$keywords.'%')->where('admin_id','not in','2,3')->order('admin_id')->select();
    	}
    	$role = D('admin_role')->getField('role_id,role_name');
    	if($res && $role){
    		foreach ($res as $val){
    			$val['role'] =  $role[$val['role_id']];
    			$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
    			$list[] = $val;
    		}
        }
    	$this->assign('list',$list);
        return $this->fetch();
    }
    
    /**
     * 修改管理员密码
     * @return \think\mixed
     */
    public function modify_pwd(){
        $admin_id = I('admin_id/d',0);
        $oldPwd = I('old_pw/s');
        $newPwd = I('new_pw/s');
        $new2Pwd = I('new_pw2/s');
       
        if($admin_id){
            $info = D('admin')->where("admin_id", $admin_id)->find();
            $info['password'] =  "";
            $this->assign('info',$info);
        }
        
         if(IS_POST){
            //修改密码
            $enOldPwd = encrypt($oldPwd);
            $enNewPwd = encrypt($newPwd);
            $admin = M('admin')->where('admin_id' , $admin_id)->find();
            if(!$admin || $admin['password'] != $enOldPwd){
                exit(json_encode(array('status'=>-1,'msg'=>'旧密码不正确')));
            }else if($newPwd != $new2Pwd){
                exit(json_encode(array('status'=>-1,'msg'=>'两次密码不一致')));
            }else{
                $row = M('admin')->where('admin_id' , $admin_id)->save(array('password' => $enNewPwd));
                if($row){
                    exit(json_encode(array('status'=>1,'msg'=>'修改成功')));
                }else{
                    exit(json_encode(array('status'=>-1,'msg'=>'修改失败')));
                }
            }
        }
        return $this->fetch();
    }
    
    public function admin_info(){
    	$admin_id = I('get.admin_id/d',0);
    	if($admin_id){
    		$info = Db::name('admin')->where("admin_id", $admin_id)->find();
			$info['password'] =  "";
    		$this->assign('info',$info);
    	}
    	$act = empty($admin_id) ? 'add' : 'edit';
    	$this->assign('act',$act);
    	$role = D('admin_role')->select();
    	$this->assign('role',$role);
    	return $this->fetch();
    }
    
    public function adminHandle(){
    	$data = I('post.');
		$adminValidate = Loader::validate('Admin');
		if(!$adminValidate->scene($data['act'])->batch()->check($data)){
			$this->ajaxReturn(['status'=>-1,'msg'=>'操作失败','result'=>$adminValidate->getError()]);
		}
		if(empty($data['password'])){
			unset($data['password']);
		}else{
			$data['password'] =encrypt($data['password']);
		}
    	if($data['act'] == 'add'){
    		unset($data['admin_id']);    		
    		$data['add_time'] = time();
			$r = D('admin')->add($data);
    	}
    	
    	if($data['act'] == 'edit'){
    		$r = D('admin')->where('admin_id', $data['admin_id'])->save($data);
    	}
        if($data['act'] == 'del' && $data['admin_id']>1){
    		$r = D('admin')->where('admin_id', $data['admin_id'])->delete();
    	}
    	
    	if($r!==false){
			$this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U('Admin/Admin/index')]);

		}else{
			$this->ajaxReturn(['status'=>-1,'msg'=>'操作失败']);
    	}
    }
    
    
    /**
     * 管理员登陆
     */
    public function login()
    {
        if (IS_POST) {
            $code = I('post.vertify');
            $username = I('post.username/s');
            $password = I('post.password/s');

            $verify = new Verify();
            if (!$verify->check($code, "admin_login")) {
                $this->ajaxReturn(['status' => 0, 'msg' => '请输入正确图形验证码']);
            }

            $adminLogic = new AdminLogic;
            $return = $adminLogic->login($username, $password);
            $this->ajaxReturn($return);
        }

        if (session('?admin_id') && session('admin_id') > 0) {
            $this->error("您已登录", U('Admin/Index/index'));
        }

        return $this->fetch();
    }
    
    /**
     * 退出登陆
     */
    public function logout()
    {
        $adminLogic = new AdminLogic;
        $adminLogic->logout(session('admin_id'));

        $this->success("退出成功",U('Admin/Admin/login'));
    }
    
    /**
     * 验证码获取
     */
    public function vertify()
    {
        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'useCurve' => false,
            'useNoise' => false,
        	'reset' => false
        );    
        $Verify = new Verify($config);
        $Verify->entry("admin_login");
        exit();
    }
    
    public function role(){
    	$list = D('admin_role')->order('role_id desc')->select();
    	$this->assign('list',$list);
    	return $this->fetch();
    }
    
    public function role_info(){
    	$role_id = I('get.role_id/d');
    	$detail = array();
    	if($role_id){
    		$detail = M('admin_role')->where("role_id",$role_id)->find();
    		$detail['act_list'] = explode(',', $detail['act_list']);
    		$this->assign('detail',$detail);
    	}
		$right = M('system_menu')->order('id')->select();
		foreach ($right as $val){
			if(!empty($detail)){
				$val['enable'] = in_array($val['id'], $detail['act_list']);
			}
			$modules[$val['group']][] = $val;
		}
		//admin权限组
        $group = (new ModuleLogic)->getPrivilege(0);
		$this->assign('group',$group);
		$this->assign('modules',$modules);
    	return $this->fetch();
    }
    
    public function roleSave(){
    	$data = I('post.');
    	$res = $data['data'];
    	$res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
        if(empty($res['act_list']))
            $this->error("请选择权限!");        
    	if(empty($data['role_id'])){
			$admin_role = Db::name('admin_role')->where(['role_name'=>$res['role_name']])->find();
			if($admin_role){
				$this->error("已存在相同的角色名称!");
			}else{
				$r = D('admin_role')->add($res);
			}
    	}else{
			$admin_role = Db::name('admin_role')->where(['role_name'=>$res['role_name'],'role_id'=>['<>',$data['role_id']]])->find();
			if($admin_role){
				$this->error("已存在相同的角色名称!");
			}else{
				$r = D('admin_role')->where('role_id', $data['role_id'])->save($res);
			}
    	}
		if($r){
			adminLog('管理角色');
			$this->success("操作成功!",U('Admin/Admin/role_info',array('role_id'=>$data['role_id'])));
		}else{
			$this->error("操作失败!",U('Admin/Admin/role'));
		}
    }
    
    public function roleDel(){
    	$role_id = I('post.role_id/d');
    	$admin = D('admin')->where('role_id',$role_id)->find();
    	if($admin){
    		exit(json_encode("请先清空所属该角色的管理员"));
    	}else{
    		$d = M('admin_role')->where("role_id", $role_id)->delete();
    		if($d){
    			exit(json_encode(1));
    		}else{
    			exit(json_encode("删除失败"));
    		}
    	}
    }
    
    public function log(){
    	$p = I('p/d',1);
    	$logs = DB::name('admin_log')->alias('l')->join('__ADMIN__ a','a.admin_id =l.admin_id')->order('log_time DESC')->page($p.',20')->select();
    	$this->assign('list',$logs);
    	$count = DB::name('admin_log')->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
    	return $this->fetch();
    }


	/**
	 * 供应商列表
	 */
	public function supplier()
	{
	    $where="s.sup_type=1 or (s.sup_type=2 and s.is_check in (1,4,5))";
        I('suppliers_name') && $condition['suppliers_name']=['like',"%".I('suppliers_name')."%"];
        I('suppliers_contacts') && $condition['suppliers_contacts']=['like',"%".I('suppliers_contacts')."%"];
        I('suppliers_phone') && $condition['suppliers_phone']=['like',"%".I('suppliers_phone')."%"];
        (I('customs_status') !== '') && $condition['customs_status']=I('customs_status');
        (I('is_self') !== '') && $condition['is_self']=I('is_self');
        (I('is_statistics') !== '') && $condition['is_statistics']=I('is_statistics');
		$supplier_count = DB::name('suppliers')->alias('s')->where($where)->where($condition)->count();
		$page = new Page($supplier_count, 10);
		$supplier_list = DB::name('suppliers')
				->alias('s')
				->field('s.*,a.admin_id,a.user_name')
				->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
                ->where($where)->where($condition)
				->limit($page->firstRow, $page->listRows)
				->select();
		$this->assign('list', $supplier_list);
		$this->assign('pager', $page);
		return $this->fetch();
	}

	/**
	 * 供应商资料
	 */
	public function supplier_info()
	{
		$suppliers_id = I('get.suppliers_id/d', 0);
		if ($suppliers_id) {
			$info = DB::name('suppliers')
					->alias('s')
					->field('s.*,a.admin_id,a.user_name')
					->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
					->where(array('s.suppliers_id' => $suppliers_id))
					->find();
			$this->assign('info', $info);
		}
		$act = empty($suppliers_id) ? 'add' : 'edit';
		$this->assign('act', $act);
		$admin = M('admin')->field('admin_id,user_name')->select();
		$this->assign('admin', $admin);
		return $this->fetch();
	}

	/**
	 * 供应商增删改
	 */
	public function supplierHandle()
	{
		$data = I('post.');
//		halt($data);
        $data['shop_rate']=(float)$data['shop_rate'];
//        $data['market_rate']=(float)$data['market_rate'];
        $data['shipping_area']=trim($data['shipping_area']);
		$suppliers_model = M('suppliers');
//		if($data['price_fun']==1){
//            $data['shop_rate']=1;
//            $data['market_rate']=1;
//        }
		//增
		if ($data['act'] == 'add') {
			unset($data['suppliers_id']);
			$count = $suppliers_model->where("suppliers_name", $data['suppliers_name'])->count();
			if ($count) {
				$this->error("此供应商名称已被注册，请更换", U('Admin/Admin/supplier_info'));
			} else {
				$r = $suppliers_model->insertGetId($data);
				if (!empty($data['admin_id'])) {
					$admin_data['suppliers_id'] = $r;
					M('admin')->where(array('suppliers_id' => $admin_data['suppliers_id']))->save(array('suppliers_id' => 0));
					M('admin')->where(array('admin_id' => $data['admin_id']))->save($admin_data);
				}
			}
		}
		//改
		if ($data['act'] == 'edit' && $data['suppliers_id'] > 0) {
		    $sup_info=Db::name('suppliers')->where('suppliers_id',$data['suppliers_id'])->find();
			$r = $suppliers_model->where('suppliers_id',$data['suppliers_id'])->save($data);
			if (!empty($data['admin_id'])) {
				$admin_data['suppliers_id'] = $data['suppliers_id'];
				$suppliers = $suppliers_model->where('suppliers_id',$data['suppliers_id'])->find();
				$admin_data['city_id'] = $suppliers['city_id'];
				$admin_data['province_id'] = $suppliers['province_id'];
				M('admin')->where(array('admin_id' => $data['admin_id']))->save($admin_data);
			}
			//找到所有该供应商的商品，如果商品已经设置了比例就不管，没设置就修改售价和市场价
			$suppliersCode = Db::name('suppliers')->where('suppliers_id',$data['suppliers_id'])->value('suppliers_code');
			/*$id = Db::name('spec_item')->where('item',$suppliersCode)->value('id');
			$all = Db::name('spec_goods_price')->field('item_id,key,goods_id')->select();
			$goods = [];$item = [];
			foreach($all as $v){
				if(in_array($id,explode('_',$v['key']))){
					$goods[] = $v['goods_id'];
					$item[$v['goods_id']].= $v['item_id'].',';
				}
			}
			if(!empty($goods)){
				$goods = array_unique($goods);
				$goods = implode(',',$goods);
				$notSetRategoods = Db::name('goods')->field('goods_id')->where('goods_id','in',$goods)->where(function($query) {
					$query->where('shop_rate','lt',1)->whereor('shop_rate',null);
				})->select();
				foreach($notSetRategoods as $v) {
					$item[$v['goods_id']] = trim($item[$v['goods_id']],',');
					Db::name('spec_goods_price')->where('item_id','in',$item[$v['goods_id']])->update(['price' => ['exp', 'cost_price*' . $data['shop_rate']], 'market_price' => ['exp', 'cost_price*' . $data['market_rate']]]);
				}
			}*/
            if($data['shop_rate']!=$sup_info['shop_rate']){
                $notSetRategoods = Db::name('spec_goods_price')->field('suppliers_id')->where(function($query) {
                    $query->where('shop_rate','lt',1)->whereor('shop_rate',null);
                })->select();
                foreach($notSetRategoods as $v) {
                    Db::name('spec_goods_price')->where(['suppliers_id'=>$data['suppliers_id'],'item_id'=>$v['item_id']])->update(['price' => ['exp', 'cost_price*' . $data['shop_rate']], 'market_price' =>0]);// ['exp', 'cost_price*' . $data['market_rate']]
                }
            }
		}
		//删
		if ($data['act'] == 'del' && $data['suppliers_id'] > 0) {
			$r = $suppliers_model->where('suppliers_id', $data['suppliers_id'])->delete();
			M('admin')->where(array('suppliers_id' => $data['suppliers_id']))->save(array('suppliers_id' => 0));
			if($r){
				respose(1);
			}else{
				respose('删除失败');
			}
		}

		if ($r !== false) {
			clearCache();
			$this->success("操作成功", U('Admin/Admin/supplier'));
		} else {
			$this->error("操作失败", U('Admin/Admin/supplier'));
		}
	}

	//子商户列表 和银盛支付的对接需要
    public function secondary_business_list()
    {
       $business= M('secondary_business')->select();
       $this->assign('list',$business);
       return $this->fetch();

    }

    public function add_business()
    {
        return $this->fetch();
    }

    public function edit_business()
    {
        $id=I('get.id');
        $info=M('secondary_business')->find($id);
        if(!$info)return $this->error('非法操作');
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function businessHandle()
    {
        $post=I('post.');
        if($post['cust_type']=='O'){
            if (!$post['id_pos']||!$post['id_neg']||!$post['card_pos']||!$post['card_neg']||!$post['protocol']) {
                return $this->error('客户类型为小微商户、结算到银行卡时，法人身份证正反面照、结算银行卡正反面照、客户协议为必传' );
            }
        }
        if($post['cust_type']=='C'){
            if (!$post['id_pos']||!$post['id_neg']||!$post['hand_id_pos']||!$post['license']||!$post['door']||!$post['card_pos']||!$post['card_neg']||!$post['protocol']) {
                return $this->error('客户类型为个体商户时，图片类型：法人身份证正反面照、法人手持身份证正扫面照、营业执照、门头照、结算银行卡正反面照，客户协议为必传 ');

            }
        }
        if($post['cust_type']=='B'){
            if (!$post['id_pos']||!$post['id_neg']||!$post['hand_id_pos']||!$post['license']||!$post['door']||!$post['protocol']||!$post['printing_card']) {
                return  $this->error('企业商户结算到银行卡时，图片类型法人身份证正反面照、法人手持身份证正扫面照、营业执照、门头照，客户协议、开户许可证/印鉴卡 ,备注：企业商户若结算账户为基本户，上传开户许可证；若为一般户，上传印鉴卡');

            }
        }

        if(!$post['id']){
            $address=M('region')->column('name','id');
            $post['province']=$address[$post['province']];
            $post['city']=$address[$post['city']];
            $post['district']=$address[$post['district']];
            if($post['city']=='市辖县'||$post['city']=='市辖区'||$post['city']=='县')$post['city']=$post['province'];
            $id=M('secondary_business')->insertGetId($post);
            if ($id) {
                   require_once(PLUGIN_PATH.'payment/yinsheng/yinsheng.class.php');
                  $yinsheng= new \yinSheng();
                $token= $yinsheng->get_token();
                if($post['id_pos']){
                    $r1=$yinsheng->upload_picture('00',$post['id_pos'],$token);
                    if (!$r1['isSuccess']) {
                        return  $this->error('上传身份证正面失败');
                    }
                };
                if($post['id_neg']){
                    $r1= $yinsheng->upload_picture('30',$post['id_neg'],$token);
                    if (!$r1['isSuccess']) {
                        return  $this->error('上传身份证反面失败');
                    }
                };
                if($post['hand_id_pos']){
                    $r1=$yinsheng->upload_picture('33',$post['hand_id_pos'],$token);
                    if (!$r1['isSuccess']) {
                        return  $this->error('上传手持身份证失败');
                    }
                };
                if($post['door']){
                    $r1=$yinsheng->upload_picture('34',$post['door'],$token);
                    if (!$r1['isSuccess']) {
                        return  $this->error('上传门头照失败');
                    }
                };
                if($post['card_pos']){
                    $r1=$yinsheng->upload_picture('35',$post['card_pos'],$token);
                    if (!$r1['isSuccess']) {
                        return  $this->error('上传银行卡正面失败');
                    }
                };
                if($post['card_neg']){
                    $r1=$yinsheng->upload_picture('36',$post['card_neg'],$token);
                    if (!$r1['isSuccess']) {
                        return  $this->error('上传银行卡反面失败');
                    }
                };
                if($post['protocol']){
                    $r1=$yinsheng->upload_picture('31',$post['protocol'],$token);
                    if (!$r1['isSuccess']) {
                        return  $this->error('上传协议失败');
                    }
                };
                if($post['printing_card']){
                    $r1=$yinsheng->upload_picture('37',$post['printing_card'],$token);
                    if (!$r1['isSuccess']) {
                        return  $this->error('上传许可证或印鉴卡失败');
                    }
                };
                if($post['license']){
                    $r1=$yinsheng->upload_picture('19',$post['license'],$token);
                    if (!$r1['isSuccess']) {
                        return  $this->error('营业执照');
                    }
                };
                $res= $yinsheng->register($token,$post);
                //注册同步通知的内容记录日志
                M('yinsheng_register_log')->insert($res);
                if($res['code']!='10000'){
                    M('secondary_business')->where('id',$id)->delete();
                    return $this->error($res['sub_msg']);
                }
                M('secondary_business')->where('id',$id)->save($res);
                return $this->success('操作成功', U('Admin/Admin/secondary_business_list'));
            }else{
                return $this->error('操作失败');
            }
        }else{
            $id=$post['id'];
            unset($post['id']);
            require_once(PLUGIN_PATH.'payment/yinsheng/yinsheng.class.php');
            $yinsheng= new \yinSheng();
            $token= $yinsheng->get_token();
            $yinsheng->register($token,$post);
            if (M('secondary_business')->where('id',$id)->save($post)) {
                return $this->success('操作成功', U('Admin/Admin/secondary_business_list'));
            }else{
                return $this->error('操作失败');
            }
        }




    }

    /**
     * 子系统对接列表
     * @return mixed
     */
    public function subsystemList()
    {
        $subsystem= M('subsystem')->select();
        $this->assign('list',$subsystem);
        return $this->fetch();
    }

    /**
     * 供应商结算账单
     * @return mixed
     */
    public function bill_settlement()
    {
        $supplier_count = DB::name('suppliers')->count();
        $page = new Page($supplier_count, 10);
        $supplier_list = DB::name('suppliers')
            ->alias('s')
            ->field('s.*,a.admin_id,a.user_name')
            ->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $this->assign('list', $supplier_list);
        $this->assign('pager', $page);
        return $this->fetch();
    }

    /**
     * 供应商结算账单详情
     * @return mixed
     */
    public function bill_info()
    {
        $supplier_count = DB::name('suppliers')->count();
        $page = new Page($supplier_count, 10);
        $supplier_list = DB::name('suppliers')
            ->alias('s')
            ->field('s.*,a.admin_id,a.user_name')
            ->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $this->assign('list', $supplier_list);
        $this->assign('pager', $page);
        $this->assign('k','info');
        return $this->fetch();
    }

    /**
     * 供应商结算账单详情
     * @return mixed
     */
    public function refund_details()
    {
        $supplier_count = DB::name('suppliers')->count();
        $page = new Page($supplier_count, 10);
        $supplier_list = DB::name('suppliers')
            ->alias('s')
            ->field('s.*,a.admin_id,a.user_name')
            ->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $this->assign('list', $supplier_list);
        $this->assign('pager', $page);
        $this->assign('k','details');
        return $this->fetch();
    }

    /**
     * 供应商结算账单详情
     * @return mixed
     */
    public function bill_other()
    {
        $supplier_count = DB::name('suppliers')->count();
        $page = new Page($supplier_count, 10);
        $supplier_list = DB::name('suppliers')
            ->alias('s')
            ->field('s.*,a.admin_id,a.user_name')
            ->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $this->assign('list', $supplier_list);
        $this->assign('pager', $page);
        $this->assign('k','other');
        return $this->fetch();
    }

    /**
     * 订单结算
     * @return mixed
     */
    public function order_settlement()
    {
        $condition = array('shop_id' => 0);

        $consignee = I('consignee', '');
        if ($consignee) {
            $condition['consignee|mobile'] = ['like', "%$consignee%"];
        }
        $order_sn = I('order_sn', '');
        if ($order_sn) $condition['order_sn|subsystem_sn'] = trim($order_sn);
        $request_order_sn = I('request_order_sn', '');
        if ($request_order_sn) $condition['request_order_sn'] = trim($request_order_sn);
        $goods_name = I('goods_name', '', 'trim');
        if ($goods_name) {
            $order_ids = M("order_goods")->where("goods_name", "like", "%$goods_name%")->column("order_id");
            $condition['order_id'] = ["in", implode(',', $order_ids)];
        }
        $bar_code = I('bar_code', '', 'trim');
        if ($bar_code) {
            $order_ids = M("order_goods")->where("bar_code", "like", "%$bar_code%")->column("order_id");
            $condition['order_id'] = ["in", implode(',', $order_ids)];
        }

        $begin = strtotime(I('start_time'));
        $end = strtotime(I('end_time'));
        $pay_start_time = strtotime(I('pay_start_time'));
        $pay_end_time = strtotime(I('pay_end_time'));
        if ($begin && !$end) {
            $condition['add_time'] = array('gt', $begin);
        }
        if (!$begin && $end) {
            $condition['add_time'] = array('lt', $end);
        }
        if ($begin && $end) {
            $condition['add_time'] = array('between', "$begin,$end");
        }

        if ($pay_start_time && !$pay_end_time) {
            $condition['pay_time'] = array('gt', $pay_start_time);
        }
        if (!$pay_start_time && $pay_end_time) {
            $condition['pay_time'] = array('lt', $pay_end_time);
        }
        if ($pay_start_time && $pay_end_time) {
            $condition['pay_time'] = array('between', "$pay_start_time,$pay_end_time");
        }
        $condition['prom_type'] = array('lt', 5);
//        $order_sn = ($keyType && $keyType == 'order_sn') ? $keywords : I('order_sn') ;
//        $order_sn ? $condition['order_sn'] = trim($order_sn) : false;

//        I('order_status') != '' ? $condition['order_status'] = I('order_status') : false;
        if (I('is_send') != '') $condition['is_send'] = I('is_send');
        if (I('customs2status') != '') $condition['customs2status'] = I('customs2status');
        I('pay_status') != '' ? $condition['pay_status'] = I('pay_status') : false;

        $condition['pay_status'] = 1;
        $condition['pay_code'] = ['not in', ''];

        //I('pay_code') != '' ? $condition['pay_code'] = I('pay_code') : false;
        if (I('pay_code')) {
            switch (I('pay_code')) {
                case '余额支付':
                    $condition['pay_name'] = I('pay_code');
                    break;
                case '积分兑换':
                    $condition['pay_name'] = I('pay_code');
                    break;
                case 'alipay':
                    $condition['pay_code'] = ['in', ['alipay', 'alipayMobile']];
                    break;
                case 'weixin':
                    $condition['pay_code'] = ['in', ['weixin', 'weixinH5', 'miniAppPay']];
                    break;
                case '其他方式':
                    $condition['pay_name'] = '';
                    $condition['pay_code'] = '';
                    break;
                default:
                    $condition['pay_code'] = I('pay_code');
                    break;
            }
        }

        I('shipping_status') != '' ? $condition['shipping_status'] = I('shipping_status') : false;
        I('sub_account') != '' ? $condition['sub_account'] = I('sub_account') : false;
//        I('user_id') ? $condition['user_id'] = trim(I('user_id')) : false;
        if ($condition['order_status'] == 2) {
            $condition['shipping_status'] = 1;
            $condition['order_status'] = 1;
        }
        $users = I('user', '');
        if ($users) {
            $uIds = M('users')->where('nickname|mobile|real_name', 'like', "%$users%")->column('user_id');
            $condition['user_id'] = ["in", implode(',', $uIds)];
        }
        $suppliers = I('suppliers', '');
        if ($suppliers) {
            $suppliers = M('suppliers')->where('suppliers_name', 'like', "%$suppliers%")->column('suppliers_code');
            $suppliers_code = $suppliers[0];
            $oIds = M('order_goods')->where('spec_key_name', 'like', "%$suppliers_code%")->column('order_id');
            $condition['order_id'] = ["in", implode(',', $oIds)];
        }
        $sort_order = I('order_by', 'DESC') . ' ' . I('sort');
        $count = Db::name('order')->where($condition)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $total_amount = 0;
        $total_pay = 0;
        $total_all_amount = 0;
        $total_all_pay = 0;
        $orderList = Db::name('order')->where($condition)->limit($Page->firstRow, $Page->listRows)->order('order_id desc')->select();
        foreach ($orderList as $k => $v) {
            if ($v['deleted'] == 1) {
                $orderList[$k]['o_status'] = '已删除';
            } else if ($v['pay_status'] == 0 && $v['order_status'] == 1 && $v['pay_code'] != "cod") {
                $orderList[$k]['o_status'] = '待支付';
            } else if (($v['pay_status'] == 1 || $v['pay_code'] == "cod") && $v['shipping_status'] == 2 && ($v['order_status'] == 0 || $v['order_status'] == 1)) {
                $orderList[$k]['o_status'] = '部分发货';
            } else if (($v['pay_status'] == 1 || $v['pay_code'] == "cod") && $v['shipping_status'] == 0 && ($v['order_status'] == 0 || $v['order_status'] == 1)) {
                $orderList[$k]['o_status'] = '待发货';
            }else if ($v['shipping_status'] == 1 && $v['order_status'] == 1) {
                $orderList[$k]['o_status'] = '待收货';
            } else if ($v['order_status'] == 4) {
                $orderList[$k]['o_status'] = '已完成';
            } else if ($v['order_status'] == 5) {
                $orderList[$k]['o_status'] = '已退款';
            } else if ($v['order_status'] == 3) {
                $orderList[$k]['o_status'] = '已取消';
            }
            $selectGoods = Db::name('order_goods')->where('order_id', $v['order_id'])->order('order_id desc')->select();
            $orderList[$k]['total_cost'] = 0;
            $goods_num = 0;
            foreach ($selectGoods as $g) {
                $orderList[$k]['suppliers_id'] = $g['suppliers_id'];
                $orderList[$k]['total_cost'] += $g['cost_price'] * $g['goods_num'];
                $goods_num = $goods_num + $g['goods_num'];
            }
            $orderList[$k]['goods_num'] = $goods_num;
            $suppliers = Db::name('suppliers')->where('suppliers_id', $orderList[$k]['suppliers_id'])->find();
            $orderList[$k]['suppliers_name'] = $suppliers['suppliers_name'];
            $orderList[$k]['total_cost'] = number_format($orderList[$k]['total_cost'], 2, '.', '');
            $total_amount += $v['goods_price'];
            $total_pay += $v['order_amount'];
        }
        $allOrderList = Db::name('order')->where($condition)->select();
        foreach ($allOrderList as $v) {
            $total_all_amount += $v['goods_price'];
            $total_all_pay += $v['order_amount'];
        }
        $this->assign('orderList', $orderList);
        $this->assign('total_amount', formatTwoPoints($total_amount));
        $this->assign('total_pay', formatTwoPoints($total_pay));
        $this->assign('total_all_amount', formatTwoPoints( $total_all_amount));
        $this->assign('total_all_pay', formatTwoPoints( $total_all_pay));
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('pager', $Page);
        return $this->fetch();
    }


    public function add_subsystem()
    {
        return $this->fetch();
    }

    public function edit_subsystem()
    {
        $id=I('get.id');
        $info=M('subsystem')->find($id);
        if(!$info)return $this->error('非法操作');
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function subsystemHandle()
    {
        $post=I('post.');
        if($post['subsystem_name']=='')return $this->error('子系统名称不能为空');
        if($post['subsystem_mobile']=='')return $this->error('子系统电话不能为空');
        $user=M('users')->where('mobile',$post['subsystem_mobile'])->find();
        if(!$user)return $this->error('当前子系统电话不存在，请先用该电话注册');
        if(!$post['id']){
            if(M('subsystem')->where('subsystem_name',$post['subsystem_name'])->find())return $this->error('子系统名称已存在，不可新增！');
            if(M('subsystem')->where('user_id',$user['user_id'])->find())return $this->error('子系统手机已存在，不可新增！');
            $post['user_id']=$user['user_id'];
            $id=M('subsystem')->insertGetId($post);
            if ($id) {
                return $this->success('操作成功', U('Admin/Admin/subsystemList'));
            }else{
                return $this->error('操作失败');
            }
        }else{
            $subsystem_id=$post['id'];
            unset($post['id']);
            $post['user_id']=$user['user_id'];
            if(M('subsystem')->where('subsystem_name',$post['subsystem_name'])->where('subsystem_id','neq',$subsystem_id)->find())return $this->error('子系统名称已存在,不可修改！');
            if(M('subsystem')->where('user_id',$user['user_id'])->where('subsystem_id','neq',$subsystem_id)->find())return $this->error('子系统手机已存在,不可修改！');
            if(M('subsystem')->where('subsystem_id',$subsystem_id)->save($post)) {
                return $this->success('操作成功', U('Admin/Admin/subsystemList'));
            }else{
                return $this->error('操作失败');
            }
        }
    }

    public function subsystemDel()
    {
        $subsystem_id=I('subsystem_id',0);
        if(!$subsystem_id)return false;
        $subsystem=M('subsystem')->find($subsystem_id);
        if(!$subsystem)return false;
        return M('subsystem')->where('subsystem_id',$subsystem_id)->save(['deleted'=>1]);
    }
    public function subsystemEnable()
    {
        $subsystem_id=I('subsystem_id',0);
        if(!$subsystem_id)return false;
        $subsystem=M('subsystem')->find($subsystem_id);
        if(!$subsystem)return false;
        return M('subsystem')->where('subsystem_id',$subsystem_id)->save(['deleted'=>0]);
    }

    public function sup_role(){
        $list = D('supplier_role')->order('role_id desc')->select();
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function sup_role_info(){
        $role_id = I('get.role_id/d');
        $detail = array();
        if($role_id){
            $detail = M('supplier_role')->where("role_id",$role_id)->find();
            $detail['act_list'] = explode(',', $detail['act_list']);
            $this->assign('detail',$detail);
        }
        $right = M('supplier_menu')->order('id')->select();
        foreach ($right as $val){
            if(!empty($detail)){
                $val['enable'] = in_array($val['id'], $detail['act_list']);
            }
            $modules[$val['group']][] = $val;
        }
        //admin权限组
        $group = (new ModuleLogic)->getPrivilege(0);
        $this->assign('group',$group);
        $this->assign('modules',$modules);
        return $this->fetch();
    }

    public function sup_role_save(){
        $data = I('post.');
        $res = $data['data'];
        $res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
        if(empty($res['act_list']))
            $this->error("请选择权限!");
        if(empty($data['role_id'])){
            $supplier_role = Db::name('supplier_role')->where(['role_name'=>$res['role_name']])->find();
            if($supplier_role){
                $this->error("已存在相同的角色名称!");
            }else{
                $r = D('supplier_role')->add($res);
            }
        }else{
            $supplier_role = Db::name('supplier_role')->where(['role_name'=>$res['role_name'],'role_id'=>['<>',$data['role_id']]])->find();
            if($supplier_role){
                $this->error("已存在相同的角色名称!");
            }else{
                $r = D('supplier_role')->where('role_id', $data['role_id'])->save($res);
            }
        }
        if($r){
            adminLog('管理角色');
            $this->success("操作成功!",U('Admin/Admin/sup_role_info',array('role_id'=>$data['role_id'])));
        }else{
            $this->error("操作失败!",U('Admin/Admin/sup_role'));
        }
    }

    public function sup_role_del(){
        $role_id = I('post.role_id/d');
        $admin = D('admin')->where('role_id',$role_id)->find();
        if($admin){
            exit(json_encode("请先清空所属该角色的管理员"));
        }else{
            $d = M('supplier_role')->where("role_id", $role_id)->delete();
            if($d){
                exit(json_encode(1));
            }else{
                exit(json_encode("删除失败"));
            }
        }
    }

    public function  exportSuppliers(){
        $list_ids = I('list_ids','');
        if($list_ids){
            $condition['suppliers_id'] = ['in', $list_ids];
        }

        $where="s.sup_type=1 or (s.sup_type=2 and s.is_check in (1,4,5))";
        I('suppliers_name') && $condition['suppliers_name']=['like',"%".I('suppliers_name')."%"];
        I('suppliers_contacts') && $condition['suppliers_contacts']=['like',"%".I('suppliers_contacts')."%"];
        I('suppliers_phone') && $condition['suppliers_phone']=['like',"%".I('suppliers_phone')."%"];
        (I('customs_status') !== '') && $condition['customs_status']=I('customs_status');
        (I('is_self') !== '') && $condition['is_self']=I('is_self');
        (I('is_statistics') !== '') && $condition['is_statistics']=I('is_statistics');
        $supplier_list = DB::name('suppliers')
            ->alias('s')
            ->field('s.*,a.admin_id,a.user_name')
            ->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
            ->where($where)->where($condition)
            ->select();
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:*">	ID</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="120px;">供应商名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="120px;">供应商代码</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="120px;">供应商描述</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="120px;">发货地区</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">供应商联系人</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">供应商电话</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">价格比率</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">所属管理员</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">是否报关</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">是否自营</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">是否做统计</td>';
        $strTable .= '</tr>';
        if(is_array($supplier_list)){
            foreach($supplier_list as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['supperlis_id'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['suppliers_name'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['suppliers_code'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['suppliers_desc'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['shipping_area'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['suppliers_contacts'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['suppliers_phone'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['shop_rate'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['user_name'].'</td>';
                if($val['customs_status'] == 1){
                    $customs_status = '是';
                }else {
                    $customs_status = '否';
                }
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$customs_status.'</td>';
                if($val['is_self'] == 1){
                    $is_self = '是';
                }else {
                    $is_self = '否';
                }
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$is_self.'</td>';
                if($val['is_check'] == 1){
                    $is_check = '是';
                }else {
                    $is_check = '否';
                }
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$is_check.'</td>';
                if($val['is_statistics'] == 1){
                    $is_statistics = '是';
                }else {
                    $is_statistics = '否';
                }
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$is_statistics.'</td>';
                $strTable .= '</tr>';
            }
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,'Supplier');
        exit();
    }
}