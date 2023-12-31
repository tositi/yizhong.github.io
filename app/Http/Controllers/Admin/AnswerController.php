<?php

namespace App\Http\Controllers\Admin;

use App\Models\Answer;
use App\Services\CreditService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

use App\Http\Requests;

class AnswerController extends AdminController
{
    /**
     * 回答列表
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter =  $request->all();

        $query = Answer::query();

        /*提问人过滤*/
        if( isset($filter['user_id']) &&  $filter['user_id'] > 0 ){
            $query->where('user_id','=',$filter['user_id']);
        }

        /*问题过滤*/
        if( isset($filter['question_id']) &&  $filter['question_id'] > 0 ){
            $query->where('question_id','=',$filter['question_id']);
        }

        /*提问时间过滤*/
        if( isset($filter['date_range']) && $filter['date_range'] ){
            $query->whereBetween('created_at',explode(" - ",$filter['date_range']));
        }

        /*问题状态过滤*/
        if( isset($filter['status']) && $filter['status'] > -1 ){
            $query->where('status','=',$filter['status']);
        }
        $answers = $query->orderBy('created_at','desc')->paginate(20);
        return view("admin.answer.index")->with('answers',$answers)->with('filter',$filter);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /*回答审核*/
    public function verify(Request $request)
    {
        $answerIds = $request->input('id');
        // 积分策略
        $answers = Answer::whereIn('id',$answerIds)->where('status','<>',1)->select('id','user_id','question_title')->get();
        if (!empty($answers)){
            foreach ($answers as $answer){
                CreditService::create($answer->user_id,'answer',Setting()->get('coins_answer'),Setting()->get('credits_answer'),$answer->id,$answer->question_title);
            }
        }
        Answer::whereIn('id',$answerIds)->update(['status'=>1]);
        return $this->success(route('admin.answer.index').'?status=0','回答审核成功');

    }

    /**
     *删除回答
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $answerIds = $request->input('ids');
        // 给文章所有者发送站内通知
        $ids = explode(',',$answerIds);
        if ($request->input('report_type') == 99){
            $reason = $request->input('reason');
        }else{
            $reason = trans_report_type($request->input('report_type'));
        }
        foreach ($ids as $id){
            $answer = Answer::find($id);
            // 记录到通知
            NotificationService::notify(Auth()->user()->id, $answer->user_id, 'remove_answer', $answer->question_title, $answer->id, $reason);
            Answer::destroy($id);
        }
//        Answer::destroy($request->input('id'));
        return $this->success(route('admin.answer.index'),'回答删除成功');
    }
}
