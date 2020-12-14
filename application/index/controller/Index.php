<?php
namespace app\index\controller;

use app\index\model\Article;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Controller;

class Index extends Controller
{
    public function index()
    {
//        展示提交表单页面
        return view('index');
    }
//    接收表单提交的数据
    public function ins(){
//        接收参数
        $param=input();
//       验证参数
        $result = $this->validate(
           $param,
            [
//                对文章标题做必填验证，限制最多50个字符
                'name'  => 'require|max:50',
                'desc'   => 'require',
            ]);
        if(true !== $result){
            // 验证失败 输出错误信息
            $this->error($result);
        }
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息

                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                $param['file']= $info->getSaveName();
//               同步到第三方云存储
//                获取到自己的ak
                $ak="VEahUYz0rivkJsfe7wP_p8VlgMMT0JfBtDySt6Hk";
//                获取到自己的sk
                $sk="AnG6wmPNhBDSphPA3BndAtr2Jh8rf7LEmVEkFbDX";
//                new一个七牛云对象2
                $auth=new Auth($ak,$sk);
//                获取到token
                $token=$auth->uploadToken('zxd1');
                $uploads=new UploadManager();
                $key=date('Y-m-d',time());
//                同步到第三方云存储
                $uploads->putFile($token,$key,'./uploads/'.$param['file']);
//                信息入库
                $param['create_time']=time();
                $data=Article::create($param,true);
//               并存入缓存
                cache('article',$data->toArray());
                if($data){
                    return redirect('sel');
                }else{
                    $this->error('添加失败');
                }
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    public function sel(){
//        读取文章表中的数据
//        并循环展示到视图
        $data=Article::order('create_time','desc')->paginate(10);
        return view('sel',['data'=>$data]);
    }
    public function search(){
//        接收关键字进行搜索
        //        接收参数
        $param=input();
//       验证参数
        $result = $this->validate(
            $param,
            [
                'search'  => 'require',
            ]);
        if(true !== $result){
            // 验证失败 输出错误信息
            $this->error($result);
        }
        $search=input('search');
//        根据传过来的查询条件查询
        $data=Article::where("name like '%{$search}%'")->select();
       return view('search',['data'=>$data]);
    }
    public function del($id){
//        判断id是否为数字
        if(!is_numeric($id)){
            $this->error('参数不对');
        }
//        根据id删除数据
        $data=Article::destroy($id);
        if($data){
            return "<script>alert('确认要删除？');history.go(-1)</script>";
        }
    }
}
