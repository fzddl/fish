<?php

namespace App\services;

use App\Models\Course;
use App\Models\CourseOrder;
use App\Models\CourseVideo;

class CourseService
{
    public function info($id, $field = ['*'])
    {
        $data = Course::select($field)->find($id);
        if ($data) $data = $data->toArray();
        return $data;
    }

    public function courseList($param)
    {
        if (empty($param['page'])) $param['page'] = 1;
        if (empty($param['limit'])) $param['limit'] = 1;

        $query = Course::query()
            ->select('id', 'title', 'description', 'pic', 'charge_type', 'original_price', 'price', 'video_num', 'views', 'created_at')
            ->where('status', 1);

        if (!empty($param['category_id'])) {
            $query->where('category_id', $param['category_id']);
        }
        if (!empty($param['keyword'])) {
            $query->where(function ($query) use ($param) {
                $query->where('title', 'like', '%' . $param['keyword'] . '%');
                $query->orWhere('description', '%' . $param['keyword'] . '%');
            });
        }

        $total = $query->count();

        $res = $query->orderByDesc('order')
            ->orderByDesc('id')
            ->offset(($param['page'] - 1) * $param['limit'])
            ->limit($param['limit'])
            ->get();

        return [
            'page'			=> $param['page'],
            'limit'			=> $param['limit'],
            'total_limit'	=> $total,
            'lists'	=> $res,
        ];
    }

    public function videoList($param)
    {
        if (empty($param['page'])) $param['page'] = 1;
        if (empty($param['limit'])) $param['limit'] = 1;

        $query = CourseVideo::query()
            ->select('id', 'title', 'description', 'pic', 'charge_type', 'views', 'created_at')
            ->where('course_id', $param['course_id'])
            ->where('status', 1);

        if (!empty($param['keyword'])) {
            $query->where(function ($query) use ($param) {
                $query->where('title', 'like', '%' . $param['keyword'] . '%');
                $query->orWhere('description', '%' . $param['keyword'] . '%');
            });
        }

        $total = $query->count();

        $res = $query->orderByDesc('order')
            ->orderByDesc('id')
            ->offset(($param['page'] - 1) * $param['limit'])
            ->limit($param['limit'])
            ->get();

        return [
            'page'			=> $param['page'],
            'limit'			=> $param['limit'],
            'total_limit'	=> $total,
            'lists'	=> $res,
        ];
    }

    public function videoInfo($id, $uid)
    {
        $info = CourseVideo::with('course:id,charge_type,status')->find($id);
        $rtn = ['success' => false, 'msg' => ''];

        if (!$info) {
            $rtn['msg'] = 'The video is not exist';
            return $rtn;
        }

        if ($info->status !== 1 || $info->course->status !== 1) {
            $rtn['msg'] = 'The video is not exist';
        }

        if ($info->charge_type == 2) {
            $order = CourseOrder::where('course_id', $info->course_id)
                ->where('uid', $uid)
                ->where('status', 20)
                ->first();
            if (!$order) {
                $rtn['msg'] = 'You did not purchase the course';
            }
        }

        if (!empty($rtn['msg'])) {
            return $rtn;
        }

        $info->increment('views');
        $info->course()->increment('views');

        $mp4_url = env('AWS_URL') . '/' . env('AWS_BUCKET') . '/' . $info->video_url;
        $mp4_data = file_get_contents($mp4_url);

        $rtn = [
            'success' => true,
            'msg' => 'data:video/mp4;base64,' . base64_encode($mp4_data)
        ];

        return $rtn;
    }

    public function countVideo($id)
    {
        $video_total_num = CourseVideo::where('course_id', $id)->count(); //视频总数
        $video_on_num = CourseVideo::where('course_id', $id)->where('status', 1)->count(); //上架的视频总数
        $video_charge_type_1_num = CourseVideo::where('course_id', $id)->where('status', 1)->where('charge_type', 1)->count(); //已上架的免费视频数量
        $video_charge_type_2_num = CourseVideo::where('course_id', $id)->where('status', 1)->where('charge_type', 2)->count(); //已上架的收费视频数量

        if ($video_on_num > 0 && $video_on_num == $video_charge_type_2_num) {
            $charge_type = 3;
        } elseif ($video_on_num > 0 && $video_on_num == $video_charge_type_1_num) {
            $charge_type = 1;
        } elseif ($video_on_num > 0 && $video_charge_type_1_num > 0 &&  $video_charge_type_2_num > 0) {
            $charge_type = 2;
        } else {
            $charge_type = 1;
        }

        Course::where('id', $id)->update([
            'video_num' => $video_total_num,
            'charge_type' => $charge_type
        ]);

    }
}
