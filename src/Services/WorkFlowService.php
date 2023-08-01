<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\workflow\Log;
use Echoyl\Sa\Models\workflow\Node;
use Echoyl\Sa\Models\workflow\Workflow;
use Echoyl\Sa\Services\NoticeService;
use Illuminate\Support\Arr;

class WorkFlowService
{
    var $flow;
    var $model;
    var $workflow;
    var $nodes = [];
    var $data;
    var $logModel;
    var $userModel;
    var $user;
    var $userWith = [];
    /**
     * 流程初始化
     *
     * @param string $key
     * @param [type] $data 外部数据
     */
    public function __construct($data,$userModel,$user = false,$userWith = ['bumen'],$key = 'zichan_diaobo')
    {
        $model = new Workflow();
        $workflow = $model->where(['key'=>$key])->with(['nodes'=>function($q){
            $q->with(['upstream','downstream']);
        }])->first();
        if($workflow)
        {
            $workflow = $workflow->toArray();
            $this->nodes = $workflow['nodes'];
        }
        $this->workflow = $workflow;
        $this->data = $data;
        $this->userModel = $userModel;
        $this->logModel = new Log();
        $this->user = $user;
        $this->userWith = $userWith;
    }

    /**
     * 获取流程第一个节点  条件为上级节点 = 0
     *
     * @return void
     */
    public function getStartNode()
    {
        return collect($this->nodes)->first(function($v){
            return $v['upstream_id'] == 0;
        });
    }

    /**
     * 开始流程记录
     *
     * @return void
     */
    public function start()
    {
        $node = $this->getStartNode();

        if(!$node)
        {
            return;
        }
        $data = $this->data;
        $user = $this->user;
        $log = [
            'user_id'=>$user['id'],
            'user_ids'=>$user['id'],
            'node_id'=>$node['id'],
            'action'=>'start',
            'state'=>1,
            'model_id'=>$data['id'],
            'created_at'=>now(),
            'updated_at'=>now(),
            'workflow_id'=>$this->workflow['id']
        ];

        $this->logModel->insert($log);

        //检测下一个流程
        $downstream = $node['downstream'];
        if(!$downstream)
        {
            return;
        }

        $this->createDownstreamLog($downstream,true);

        return;

    }

    /**
     * 通过流程节点 的user 属性 获取当前接点可以执行的用户id
     *
     * @param [type] $node
     * @return array
     */
    public function getNodeUser($node)
    {
        $condition = $this->getNodeConfig($node,['user','condition'],[]);
        if(empty($condition))
        {
            return [];
        }
        $model = $this->userModel;
        foreach($condition as $where)
        {
            [$field,$type,$val] = $where;
            $model = $model->where([[$field,$type,$this->getDataByField($val)]]);
        }
        $user_ids = $model->get()->pluck('id')->toArray();

        return $user_ids;
    }

    public function getNodeConfig($node,$key = '',$default = '')
    {
        $config = $node['config']?json_decode($node['config'],true):[];
        if($key)
        {
            if(is_array($key))
            {
                foreach($key as $k)
                {
                    $config = Arr::get($config,$k,$default);
                }
            }else
            {
                $config = Arr::get($config,$key,$default);
            }
        }
        return $config;
    }

    public function getNodeAction($node)
    {
        $data = [
            '0'=>'结束流程'
        ];
        if($node)
        {
            $actions = $this->getNodeConfig($node,'action',[]);
            foreach($actions as $val)
            {
                $data[$val['key']] = $val;
            }
        }
        
        return $data;
    }

    /**
     * 获取所有的操作记录列表
     *
     * @return void
     */
    public function getLog()
    {
        $list = $this->logModel->where(['model_id'=>$this->data['id'],'workflow_id'=>$this->workflow['id']])->with(['node'])->orderBy('id','asc')->get()->toArray();

        $data = $timeline = [];
        
        foreach($list as $val)
        {
            $node = $val['node'];
            $is_end = false;
            $actions = $this->getNodeAction($node);
            if($val['node_id'] == 0)
            {
                $is_end = true;
            }
            
            
            $user = $action = $userable = false;
            if($val['user_id'])
            {
                //操作记录用户信息
                $user = $this->userModel->with($this->userWith)->where(['id'=>$val['user_id']])->first();
            }else
            {
                //未操作的用户获取可操作人员记录
                if($val['user_ids'])
                {
                    $userable = $this->userModel->with($this->userWith)->whereIn('id',explode(',',$val['user_ids']))->get()->toArray();
                }
            }
            //获取action
            $action_text = '';
            $action = ['title'=>'','key'=>''];
            if($val['action'])
            {
                $action = $actions[$val['action']]??$action;
                if(!$action['key'])
                {
                    $action['key'] = $val['action'];
                }
                if($is_end)
                {
                    $action_text = "流程结束";
                }else
                {
                    $action_text = $node['title'] .' '.$user['username'].' '.$action['title'].' 于 '.$val['updated_at'];
                    //添加form表单信息显示
                    $form = Arr::get($action,'form');
                    $form_text = [];
                    if(!empty($form))
                    {
                        $form_data = $val['form']?json_decode($val['form'],true):[];
                        foreach($form as $f)
                        {
                            $form_text[] = implode(':',[$f['title'],$form_data[$f['dataIndex']]??'']);
                        }
                        
                    }
                    $form_text = implode(',',$form_text);
                    $action_text .= ' '.$form_text;
                    $val['form_text'] = $form_text;
                }
            }else
            {
                $action_text = $is_end?"流程结束":($node['title'] .' 待操作');
                if($userable && !empty($userable))
                {
                    $action_text .= ' ( '.(implode(' - ',[$userable[0]['username']])).' ) ';
                }
            }

            $tl = [
                //'title'=>$node['title'],
                //'user'=>$user?$user->toArray():false,
                //'action'=>$action,
                //'time_at'=>$val['updated_at']?:'',
                //'state'=>$val['state'],
                'color'=>$val['state']?'blue':'red',
                'children'=>$action_text,
            ];
            $val['user'] = $user;
            $val['action'] = $action;
            $val['userable'] = $userable;
            $data[] = $val;
            if(!$val['state'])
            {
                $tl['icon'] = 'clock';
            }
            $timeline[] = $tl;
        }

        return ['timeline'=>$timeline,'data'=>$data];
    }

    /**
     * 获取当前待操作的node记录数据
     *
     * @return void
     */
    public function doingNode($action_url = '')
    {
        $data = $this->logModel->where([
            'model_id'=>$this->data['id'],
            'workflow_id'=>$this->workflow['id'],
            'state'=>0
        ])->with(['node'])->first();
        if(!$data)
        {
            return false;
        }

        $data = $data->toArray();

        $node = $data['node'];
        $actions = $this->getNodeConfig($node,'action',[]);

        //渲染成前端可以使用的格式actions
        $items = [];
        $user_ids = explode(',',$data['user_ids']);
        if(in_array($this->user['id'],$user_ids))
        {
            foreach($actions as $key=>$action)
            {
                $btn = ['text'=>$action['title'],'type'=>'primary','size'=>'small'];
                if(count($actions) > 1 && $action['to'] == 0)
                {
                    //数量大于1 那么to = 0 就是拒绝 按钮红色
                    if(!isset($action['color']))
                    {
                        $btn['danger'] = true;
                    }
                }
                if(isset($action['color']) && $action['color'] == 'red')
                {
                    $btn['danger'] = true;
                }
                $form = Arr::get($action,'form');
                $action_dom_type = 'confirm';
                if(!empty($form))
                {
                    $action_dom_type = 'confirmForm';
                }
                if($action['key'] == 'reedit')
                {
                    $action_dom_type = 'edit';
                }
                $item = [
                    'domtype'=>'button',
                    'btn'=>$btn,
                    'action'=>$action_dom_type,
                    'modal'=>[
                        'title'=>$node['title'],
                        'msg'=>'是否确定'.$action['title'].'？',
                        'formColumns'=>$form
                    ],
                    'request'=>[
                        'url'=>$action_url,
                        'data'=>[
                            'key'=>$action['key'],
                        ]
                    ],
                ];
                $items[] = $item;
            }
        }
        

        return ['user_ids'=>$user_ids,'actions'=>$actions,'items'=>$items,'log'=>$data,'node'=>$node];


    }

    /**
     * 操作审核记录
     *
     * @return void
     */
    public function doAction($key,$form_data = [])
    {
        $doing_node = $this->doingNode();
        if(!$doing_node)
        {
            return [1,'操作错误请重试'];
        }
        $user = $this->user;
        $user_ids = $doing_node['user_ids'];
        if(!in_array($user['id'],$user_ids))
        {
            return [1,'您不在操作人员中不能操作'];
        }
        $actions = $doing_node['actions'];

        $action = collect($actions)->first(function($item) use($key) {
            return $item['key'] == $key;
        });
        
        if(!$action)
        {
            return [1,'无该操作'];
        }

        //检测动作是否有表单提交
        $form = Arr::get($action,'form');
        $form_update = [];
        if(!empty($form))
        {
            
            foreach($form as $f)
            {
                $dataIndex = $f['dataIndex'];
                $form_update[$dataIndex] = $form_data[$dataIndex]??'';
            }
        }

        //更新当前流程记录
        $log = $doing_node['log'];
        $update = [
            'user_id'=>$this->user['id'],
            'state'=>1,
            'action'=>$action['key'],
        ];
        if(!empty($form_update))
        {
            $update['form'] = json_encode($form_update);
        }

        $downstream = $this->getNodeByTo($action['to']);

        if($downstream === 0)
        {
            //结束流程
            
            $this->logModel->where(['id'=>$log['id']])->update($update);
            $this->end();
            return [0,'操作成功'];
        }
        
        if(!$downstream)
        {
            return [1,'无该操作，请联系管理员设置'];
        }
        
        //创建下一个流程
        $flag = $this->createDownstreamLog($downstream);
        if(!$flag)
        {
            return [1,'下一步操作无人员，请先创建可操作人员信息'];
        }

        //更新当前流程记录
        $this->logModel->where(['id'=>$log['id']])->update($update);

        return [0,'操作成功'];
    }

    public function createDownstreamLog($downstream,$no_user_end = false)
    {
        $user_ids = $this->getNodeUser($downstream);

        //如果没有下一步可执行的操作用户 不允许进行下一步操作
        if(empty($user_ids))
        {
            if(!$no_user_end)
            {
                return false;
            }
            //设置了无用户则结束流程
            $this->end();
            return true;
        }

        $down_log = [
            'user_ids'=>implode(',',$user_ids),
            'node_id'=>$downstream['id'],
            'action'=>0,
            'state'=>0,
            'model_id'=>$this->data['id'],
            'created_at'=>now(),
            'workflow_id'=>$this->workflow['id']
        ];

        $this->logModel->insert($down_log);

        //添加amdin系统通知
    
        NoticeService::notification([
            'title'=>$this->workflow['title'] .' '.$downstream['title'].' 需要您去操作',
            'created_at'=>now(),
        ],$user_ids);

        return true;
    }

    public function getDataByField($val,$isField = false)
    {
        if(strpos($val,'.') !== false || $isField)
        {
            //读取数据内容
            $fields = explode('.',$val);
            $val_data = $this->data;
            foreach($fields as $v)
            {
                if(!$v)
                {
                    continue;
                }
                $val_data = $val_data[$v];
            }
            $val = $val_data;
        }
        return $val;
    }

    /**
     * Undocumented function
     *
     * @param [type] $val1
     * @param [type] $val2
     * @param [type] $op
     * @return void
     */
    public function op($val1,$val2,$op)
    {
        switch($op)
        {
            case '<':
                return $val1 < $val2;
            break;
            case '>':
                return $val1 > $val2;
            break;
            case '<=':
                return $val1 <= $val2;
            break;
            case '>=':
                return $val1 >= $val2;
            break;
            case '=':
                return $val1 == $val2;
            break;
            default:
                return false;
        }
    }   

    public function getNodeByTo($to)
    {
        $model = new Node();
        $node = $id = false;
        if(is_numeric($to))
        {
            //如果是id 直接读取该节点
            $id = $to;
            
        }elseif(is_array($to))
        {
            //获取下个节点需要更新每个条件来判断
            foreach($to as $t)
            {
                //d($t['condition']);
                if(!isset($t['condition']) || empty($t['condition']))
                {
                    continue;
                }
                $ok = true;
                foreach($t['condition'] as $where)
                {
                    [$field,$type,$val] = $where;
                    $model_val = $this->getDataByField($field,true);
                    $condition_val = $this->getDataByField($val);
                    if(!$this->op($model_val,$condition_val,$type))
                    {
                        //条件成立是and 全部条件成立
                        $ok = false;
                    }
                }
                if($ok)
                {
                    $id = $t['to'];
                    break;
                }
            }
        }

        if($id == 0)
        {
            return 0;
        }

        $node = $model->where(['id'=>$id])->first();

        if(!$node)
        {
            return false;
        }
         
        return $node->toArray();
    }

    /**
     * 结束流程
     *
     * @return void
     */
    public function end()
    {
        $down_log = [
            'action'=>'end',
            'state'=>1,
            'model_id'=>$this->data['id'],
            'created_at'=>now(),
            'workflow_id'=>$this->workflow['id']
        ];

        $this->logModel->insert($down_log);
        return;
    }

    /**
     * 检测流程是否已结束
     *
     * @return boolean
     */
    public function isEnd()
    {
        $has = $this->logModel->where([
            'workflow_id'=>$this->workflow['id'],
            'model_id'=>$this->data['id'],
            'action'=>'end'
        ])->first();
        return $has ? true:false;
    }

    /**
     * 获取流程的结果 即获取结束log的前面一条记录信息
     *
     * @return void
     */
    public function result()
    {
        $is_end = $this->isEnd();
        if(!$is_end)
        {
            //流程未结束 无结果
            return false;
        }

        $data = $this->logModel->where(['model_id'=>$this->data['id'],'workflow_id'=>$this->workflow['id']])->where([['action','!=','end']])->orderBy('id','desc')->first()->toArray();

        return $data;
    }

}
